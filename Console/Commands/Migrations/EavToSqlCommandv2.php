<?php

namespace Aurora\System\Console\Commands\Migrations;

use Aurora\System\Api;
use Aurora\System\Console\Commands\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class EavToSqlCommandV2 extends BaseCommand
{
    private $sFilePrefix = 'eav-to-sql-';

    private $iOffset = 0;
    private $iLimit = 1000;
    private $oP8Settings = false;
    private $logger = false;

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        \Aurora\Api::Init();
    }

    protected function configure(): void
    {
        $this->setName('migrate:eav-to-sql-v2')
            ->setDescription('Migrate EAV data structure to SQL')
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'The EAV database connection to use')
            ->addOption('wipe', null, InputOption::VALUE_OPTIONAL, 'Wipe current database')
            ->addOption('migrate-file', null, InputOption::VALUE_OPTIONAL, 'Migrate entites from file');
    }

    public function getMigrationFiles($paths)
    {
        return Collection::make($paths)->flatMap(function ($path) {
            return Str::endsWith($path, '.php') ? [$path] : $this->files->glob($path . '/*_*.php');
        })->filter()->values()->keyBy(function ($file) {
            return $this->getMigrationName($file);
        })->sortBy(function ($file, $key) {
            return $key;
        })->all();
    }

    protected function truncateIfExist($model)
    {
        try {
            $this->logger->info('wiping ' . $model::query()->getQuery()->from);
            if (class_exists($model)) {
                $model::truncate();
            }
            return true;
        } catch (\Illuminate\Database\QueryException $e) {
            $this->logger->warning('table doesnt exist');
        }
    }

    protected function getProperties($class, $object)
    {
        $extendedPropsUser = \Aurora\System\ObjectExtender::getInstance()->getExtendedProps($class);
        $extendedProps = [];
        foreach (array_keys($extendedPropsUser) as $extendedProp) {
            if ($object->get($extendedProp)) {
                $extendedProps[$extendedProp] = $object->get($extendedProp);
            }
        }
        return $extendedProps;
    }

    protected function rewriteFile($fd, $str)
    {
        ftruncate($fd, 0);
        fseek($fd, 0, SEEK_END);
        fwrite($fd, $str);
    }

    protected function migrateMinHashes($oConnection)
    {
        $sql = "TRUNCATE `" . $this->oP8Settings->DBPrefix . "core_min_hashes`;";
        $oConnection->execute($sql);
        $sql = "INSERT INTO " . $this->oP8Settings->DBPrefix . "core_min_hashes (`HashId`, `UserId`,`Hash`, `Data`, `ExpireDate`)
        SELECT *
        FROM " . $this->oP8Settings->DBPrefix . "min_hashes;";
        $oConnection->execute($sql);
    }

    protected function migrateActivityHistory($oConnection)
    {
        $sql = "TRUNCATE `" . $this->oP8Settings->DBPrefix . "core_activity_history`;";
        $oConnection->execute($sql);
        $sql = "INSERT INTO " . $this->oP8Settings->DBPrefix . "core_activity_history (`Id`, `UserId`, `ResourceType`,`ResourceId`, `IpAddress`, `Action`, `Timestamp`, `GuestPublicId`)
        SELECT *
        FROM " . $this->oP8Settings->DBPrefix . "activity_history;";
        $oConnection->execute($sql);
    }

    protected function wipeP9Tables()
    {
        $aModels = $this->getAllModels();
        foreach ($aModels as $modelName => $modelPath) {
            $className = str_replace('/', DIRECTORY_SEPARATOR, $modelPath);
            $className = explode(DIRECTORY_SEPARATOR, $className);
            $modelClass = [];
            while ($className[0] !== 'modules') {
                array_shift($className);
            }
            $className[0] = 'Modules';
            array_unshift($className, "Aurora");
            $className = implode(DIRECTORY_SEPARATOR, $className);
            $this->truncateIfExist($className);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $migrateEntitiesList = [];
        $offset = 0;
        $time = new \DateTime();
        $time = $time->format('YmdHis');
        $intProgress = 0;
        $dirName = \Aurora\System\Api::DataPath() . "/migration-eav-to-sql-logs";
        $filename = $dirName . "/migration-" . $time . ".txt";
        $progressFilename = $dirName . "/migration-progress.txt";
        $entitiesListFilename = $dirName . "/migration-list.txt";
        $missedEntitiesFilename = $dirName . "/migration-" . $time . "-missed-entities.txt";

        $dirname = dirname($filename);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }

        $verbosityLevelMap = array(
            'notice' => OutputInterface::VERBOSITY_NORMAL,
            'info' => OutputInterface::VERBOSITY_NORMAL,
        );

        $fdProgress = fopen($progressFilename, 'a+') or die("cant create file");

        $this->logger = new ConsoleLogger($output, $verbosityLevelMap);
        $this->oP8Settings = \Aurora\System\Api::GetSettings();
        $helper = $this->getHelper('question');

        $wipe = $input->getOption('wipe');
        $migrateFile = $input->getOption('migrate-file');

        if ($wipe) {
            $question = new ConfirmationQuestion('Do you really wish to run this command? (Y/N)', false);

            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
            $this->wipeP9Tables();
        } else if ($migrateFile) {
            $fdListEntities = fopen($entitiesListFilename, 'a+') or die("cant create file");

            while (!feof($fdListEntities)) {
                $entityId = intval(htmlentities(fgets($fdListEntities)));
                if ($entityId) {
                    $migrateEntitiesList[] = $entityId;
                }
            }
            fclose($fdListEntities);
            if (count($migrateEntitiesList) === 0) {
                $this->logger->error('Entities list is empty!');
                return false;
            } else {
                $question = new ConfirmationQuestion("Do you really wish migrate " . count($migrateEntitiesList) . " entities? (Y/N)", false);
                if (!$helper->ask($input, $output, $question)) {
                    return Command::SUCCESS;
                }
            }
        } else {
            $progress = htmlentities(file_get_contents($progressFilename));
            $intProgress = intval($progress);

            if ($intProgress) {
                $question = new ConfirmationQuestion("Do you really wish to run this command from $intProgress entity? (Y/N)", false);
                if (!$helper->ask($input, $output, $question)) {
                    return Command::SUCCESS;
                }
                $cItems = DB::Table('eav_entities')->select('id')->where('id', '<=', $intProgress)->get();
                $offset = count($cItems);
            } else {
                $question = new ConfirmationQuestion("File ./logs/migration-progress.txt wrong or empty. Do you wish migrate all entities? (Y/N)", false);
                if (!$helper->ask($input, $output, $question)) {
                    return Command::SUCCESS;
                }
            }
        }

        $fdErrors = fopen($filename, 'w+') or die("cant create file");
        $fdMissedIds = fopen($missedEntitiesFilename, 'w+') or die("cant create file");

        $sql = "SELECT id, entity_type FROM `" . $this->oP8Settings->DBPrefix . "eav_entities` GROUP BY id, entity_type";
        $sqlIn = "";
        foreach ($migrateEntitiesList as $entityId) {
            $sqlIn .= $entityId . ',';
        }
        $sqlIn = substr($sqlIn, 0, -1);

        if ($migrateEntitiesList) {
            $sql = "SELECT id, entity_type FROM `" . $this->oP8Settings->DBPrefix . "eav_entities` WHERE id IN ($sqlIn) GROUP BY id, entity_type;";
        }

        $oConnection = \Aurora\System\Api::GetConnection();
        $oConnection->execute($sql);

        $entities = [];
        $passedEntities = 0;
        while (false !== ($oRow = $oConnection->GetNextRecord())) {
            if ($oRow->id <= $intProgress) {
                $passedEntities++;
                continue;
            }
            $entities[] = $oRow;
        }

        $progressBar = new ProgressBar($output, $passedEntities + count($entities));
        $progressBar->start();
        while ($passedEntities > 0) {
            $progressBar->advance();
            $passedEntities -= 1;
        }

        $this->migrateActivityHistory($oConnection);
        $this->migrateMinHashes($oConnection);
        $result = $this->migrate($fdProgress, $fdErrors, $migrateEntitiesList, $entities, $progressBar, $fdMissedIds, $wipe);

        $this->rewriteFile($fdErrors, $result['MissedEntities']);
        $this->rewriteFile($fdMissedIds, implode(PHP_EOL, $result['MissedIds']));

        fclose($fdMissedIds);
        fclose($fdErrors);
        fclose($fdProgress);
        return Command::SUCCESS;
    }

    private function migrate($fdProgress, $fdErrors, $migrateEntitiesList, $entities, $progressBar, $fdMissedIds, $wipe)
    {
        $missedEntities = [];
        $missedIds = [];
        $contactsCache = [];
        $groupsCache = [];
        $migrateArray = [];
        $truncatedTables = [];

        $modelsMap = [
            'Aurora\Modules\Mail\Classes\Account' => 'Aurora\Modules\Mail\Models\MailAccount',
            'Aurora\Modules\OAuthIntegratorWebclient\Classes\Account' => 'Aurora\Modules\OAuthIntegratorWebclient\Models\OauthAccount',
        ];

        foreach ($entities as $i => $entity) {
            try {
                $migrateArray = ['Id' => $entity->id];
                if (!class_exists($entity->entity_type)) {
                    $missedEntities[$entity->entity_type][] = $entity->id;
                    $missedIds[] = $entity->id;
                    $this->logger->warning("Entity with id " . $entity->id . " missed");
                    $progressBar->advance();
                    continue;
                }

                $aItem = collect(
                    (new \Aurora\System\EAV\Query($entity->entity_type))
                        ->where(['EntityId' => $entity->id])
                        ->asArray()
                        ->exec()
                )->first();
                $laravelModel = $modelsMap[$entity->entity_type] ?? str_replace('Classes', 'Models', $entity->entity_type);

                if ($entity->entity_type === 'Aurora\Modules\Mail\Classes\Account') {
                    $oItem = collect((new \Aurora\System\EAV\Query($entity->entity_type))
                            ->where(['EntityId' => [$entity->id, '=']])
                            ->exec())->first();
                    $migrateArray['Password'] = $oItem->getPassword();
                }

                if ($entity->entity_type === 'Aurora\Modules\Contacts\Classes\GroupContact') {
                    if (isset($contactsCache[$aItem['ContactUUID']])) {
                        $migrateArray['ContactId'] = $contactsCache[$aItem['ContactUUID']];
                    } else {
                        $contact = collect(
                            (new \Aurora\System\EAV\Query('Aurora\Modules\Contacts\Classes\Contact'))
                                ->where(['UUID' => $aItem['ContactUUID']])
                                ->asArray()
                                ->exec()
                        )->first();

                        $contactsCache[$aItem['ContactUUID']] = $contact['EntityId'];
                        $migrateArray['ContactId'] = $contact['EntityId'];
                    }

                    if (isset($groupsCache[$aItem['GroupUUID']])) {
                        $migrateArray['GroupId'] = $groupsCache[$aItem['GroupUUID']];
                    } else {
                        $group = collect(
                            (new \Aurora\System\EAV\Query('Aurora\Modules\Contacts\Classes\Group'))
                                ->where(['UUID' => $aItem['GroupUUID']])
                                ->asArray()
                                ->exec()
                        )->first();
                        $groupsCache[$aItem['GroupUUID']] = $group['EntityId'];
                        $migrateArray['GroupId'] = $group['EntityId'];
                    }
                }

                $properties = $this->getProperties($entity->entity_type, collect($aItem));
                if ($properties) {
                    $migrateArray['Properties'] = $properties;
                }

                if ($entity->entity_type === 'Aurora\Modules\Core\Classes\User' || $entity->entity_type === 'Aurora\Modules\Core\Classes\Tenant') {
                    $oItem = collect((new \Aurora\System\EAV\Query($entity->entity_type))
                            ->where(['EntityId' => [$entity->id, '=']])
                            ->exec()
                    )->first();
                    $disabledModules = $oItem->getDisabledModules();
                    if ($disabledModules) {
                        $migrateArray['Properties']['DisabledModules'] = implode('|', $disabledModules);
                    }
                }
                $newRow = $laravelModel::create(array_merge($aItem, $migrateArray));

                $this->rewriteFile($fdProgress, $entity->id);
                $this->rewriteFile($fdErrors, json_encode($missedEntities, JSON_PRETTY_PRINT));
                $this->rewriteFile($fdMissedIds, implode(PHP_EOL, $missedIds));
                $progressBar->advance();
            } catch (\Illuminate\Database\QueryException $e) {
                $errorCode = $e->getCode();
                $errorMessage = $e->getMessage();
                $shortErrorMessage = $e->errorInfo[2];
                $missedEntities[$entity->entity_type][] = $entity->id;
                $missedIds[] = $entity->id;
                $this->rewriteFile($fdErrors, json_encode($missedEntities, JSON_PRETTY_PRINT));
                $this->rewriteFile($fdMissedIds, implode(PHP_EOL, $missedIds));
                $progressBar->advance();
                switch ($errorCode) {
                    case 23000:
                        $this->logger->error("Found duplicate for entity with id $entity->id");
                        break;
                    default:
                        $this->logger->error($shortErrorMessage);
                        break;
                }
            }
        }
        $this->logger->info('Migration Completed!');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return ['MissedEntities' => json_encode($missedEntities, JSON_PRETTY_PRINT), 'MissedIds' => $missedIds];
    }
}
