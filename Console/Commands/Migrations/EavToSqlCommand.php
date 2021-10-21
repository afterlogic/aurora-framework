<?php

namespace Aurora\System\Console\Commands\Migrations;

use Aurora\System\Api;
use Aurora\System\Console\Commands\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use \Illuminate\Database\Capsule\Manager as Capsule;

class EavToSqlCommand extends BaseCommand
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
        $this->setName('migrate:eav-to-sql')
            ->setDescription('Migrate EAV data structure to SQL')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('wipe', 'w'),
                    new InputOption('migrate-file', 'f'),
                ])
            );
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
        if (!Capsule::schema()->hasTable('core_min_hashes')) {
            return false;
        }
        if (!Capsule::schema()->hasTable('min_hashes')) {
            return false;
        }

        $sql = "TRUNCATE `" . $this->oP8Settings->DBPrefix . "core_min_hashes`;";
        $oConnection->execute($sql);
        $sql = "INSERT INTO " . $this->oP8Settings->DBPrefix . "core_min_hashes (`HashId`, `UserId`,`Hash`, `Data`, `ExpireDate`)
        SELECT *
        FROM " . $this->oP8Settings->DBPrefix . "min_hashes;";
        $oConnection->execute($sql);
        return true;
    }

    protected function migrateActivityHistory($oConnection)
    {
        if (!Capsule::schema()->hasTable('core_activity_history')) {
            return false;
        }
        if (!Capsule::schema()->hasTable('activity_history')) {
            return false;
        }
        $sql = "TRUNCATE `" . $this->oP8Settings->DBPrefix . "core_activity_history`;";
        $oConnection->execute($sql);
        $sql = "INSERT INTO " . $this->oP8Settings->DBPrefix . "core_activity_history (`Id`, `UserId`, `ResourceType`,`ResourceId`, `IpAddress`, `Action`, `Timestamp`, `GuestPublicId`)
        SELECT *
        FROM " . $this->oP8Settings->DBPrefix . "activity_history;";
        $oConnection->execute($sql);
        return true;
    }

    protected function wipeP9Tables()
    {
        $aModels = $this->getAllModels();
        foreach ($aModels as $modelName => $modelPath) {
            $model = str_replace('/', DIRECTORY_SEPARATOR, $modelPath);
            $model = str_replace('\\', DIRECTORY_SEPARATOR, $model);
            $model = explode(DIRECTORY_SEPARATOR, $model);
            $modelClass = [];

            while ($model[0] !== 'modules') {
                array_shift($model);
            }
            $model[0] = 'Modules';
            array_unshift($model, "Aurora");
            $model = implode('\\', $model);
            $this->logger->info('wiping ' . $model::query()->getQuery()->from);
            $model::truncate();
        }
    }

    protected function jsonPretify($sJsonStr)
    {
        $sOutput = '{';
        $bFirstElement = true;
        foreach ($sJsonStr as $key => $value) {
            if (!$bFirstElement) {
                $sOutput .= ",";
            }
            $bFirstElement = false;

            $sOutput .= PHP_EOL . "\t\"" . $key . "\": [";
            $sOutput .= PHP_EOL . "\t\t" . implode(',', $value);
            $sOutput .= PHP_EOL . "\t]";
        }
        $sOutput .= PHP_EOL . '}';
        $sOutput = str_replace('\\', '\\\\', $sOutput);

        return $sOutput;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $migrateEntitiesList = [];
        $offset = 0;
        $time = new \DateTime();
        $time = $time->format('YmdHis');
        $intProgress = 0;
        $dirName = \Aurora\System\Api::DataPath() . "/migration-eav-to-sql";
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

        $fdProgress = file_exists($progressFilename) ? fopen($progressFilename, 'r+') : false;
        $this->logger = new ConsoleLogger($output, $verbosityLevelMap);
        $this->oP8Settings = \Aurora\System\Api::GetSettings();
        $helper = $this->getHelper('question');

        $wipe = $input->getOption('wipe');
        $migrateFile = $input->getOption('migrate-file');
        $defaultAnswer = $input->getOption('no-interaction');
        if ($wipe) {
            if(!$defaultAnswer){
                $question = new ConfirmationQuestion('Do you really wish to wipe all data in target tables? (Y/N)', $defaultAnswer);
    
                if (!$helper->ask($input, $output, $question)) {
                    return Command::SUCCESS;
                }
            }
            $this->wipeP9Tables();
        } else if ($migrateFile) {
            $sEntitiesList = @file_get_contents($entitiesListFilename, true);
            if (!$sEntitiesList) {
                $this->logger->error('Entities list is empty!');
                return false;
            }

            $migrateEntitiesList = explode(',', $sEntitiesList);
            if(!$defaultAnswer){
            $question = new ConfirmationQuestion("Proceed with migrating " . count($migrateEntitiesList) . " entities? (Y/N)", $defaultAnswer);
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }
        } else {
            if ($fdProgress) {
                $progress = htmlentities(file_get_contents($progressFilename));
                $intProgress = intval($progress);
                if ($intProgress) {
                    if(!$defaultAnswer){
                    $question = new ConfirmationQuestion("Resume from entity with ID $intProgress (last successfully migrated)? (Y/N)", $defaultAnswer);
                    if (!$helper->ask($input, $output, $question)) {
                        return Command::SUCCESS;
                    }
                }
                    $cItems = DB::Table('eav_entities')->select('id')->where('id', '<=', $intProgress)->get();
                    $offset = count($cItems);
                } else {
                    if(!$defaultAnswer){
                    $question = new ConfirmationQuestion("File $progressFilename is invalid. Do you wish migrate all entities? (Y/N)", $defaultAnswer);
                    if (!$helper->ask($input, $output, $question)) {
                        return Command::SUCCESS;
                    }
                }
                }
            }
        }

        $fdProgress = fopen($progressFilename, 'a+') or die("Can't create migration-progress.txt file");
        $fdErrors = fopen($filename, 'w+') or die("Can't create migration-" . $time . ".txt file");
        $fdMissedIds = fopen($missedEntitiesFilename, 'w+') or die("Can't create migration-" . $time . "-missed-entities.txt file");

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
        $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:6s% %memory:6s%');
        $progressBar->start();
        $progressBar->advance($passedEntities);

        $this->migrateActivityHistory($oConnection);
        $this->migrateMinHashes($oConnection);
        $result = $this->migrate($fdProgress, $fdErrors, $migrateEntitiesList, $entities, $progressBar, $fdMissedIds, $wipe);
        $this->rewriteFile($fdErrors, $this->jsonPretify($result['MissedEntities']));
        $this->rewriteFile($fdMissedIds, implode(',', $result['MissedIds']));

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
            'Aurora\Modules\Mail\Classes\Sender' => 'Aurora\Modules\Mail\Models\TrustedSender'
        ];

        foreach ($entities as $i => $entity) {
            try {
                $migrateArray = ['Id' => $entity->id];
                if (!class_exists($entity->entity_type)) {
                    $missedEntities[$entity->entity_type][] = $entity->id;
                    $missedIds[] = $entity->id;
                    $this->logger->warning("Didn't find EAV class for entity with id $entity->id, skipping.");

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

                switch ($entity->entity_type) {

                    case 'Aurora\Modules\StandardAuth\Classes\Account':
                        $oItem = collect((new \Aurora\System\EAV\Query($entity->entity_type))
                                ->where(['EntityId' => [$entity->id, '=']])
                                ->exec())->first();
                        $password = str_replace($oItem->Login, '', $oItem->Password);
                        $migrateArray['Password'] = $password;
                        break;

                    case 'Aurora\Modules\Mail\Classes\Account':
                        $oItem = collect((new \Aurora\System\EAV\Query($entity->entity_type))
                                ->where(['EntityId' => [$entity->id, '=']])
                                ->exec())->first();
                        $migrateArray['IncomingPassword'] = $oItem->getPassword();
                        break;

                    case 'Aurora\Modules\Contacts\Classes\GroupContact':
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
                        break;

                    default:
                        break;
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
                $progressBar->advance();

            } catch (\Illuminate\Database\QueryException $e) {
                $errorCode = $e->getCode();
                $errorMessage = $e->getMessage();
                $shortErrorMessage = $e->errorInfo[2];
                $missedEntities[$entity->entity_type][] = $entity->id;
                $missedIds[] = $entity->id;
                $progressBar->advance();
                switch ($errorCode) {
                    case 23000:
                        $this->logger->error("Found duplicate for entity with id $entity->id, skipping.");
                        break;

                    default:
                        $this->logger->error($shortErrorMessage);
                        break;
                }
            } finally {
                $this->rewriteFile($fdErrors, $this->jsonPretify($missedEntities));
                $this->rewriteFile($fdMissedIds, implode(',', $missedIds));
            }
        }
        $this->logger->info('Migration Completed!');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return ['MissedEntities' => $missedEntities, 'MissedIds' => $missedIds];
    }
}
