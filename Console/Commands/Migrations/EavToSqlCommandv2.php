<?php

namespace Aurora\System\Console\Commands\Migrations;

use Aurora\Modules\Contacts\Models\Contact;
use Aurora\Modules\Contacts\Models\CTag;
use Aurora\Modules\Contacts\Models\Group;
use Aurora\Modules\Contacts\Models\GroupContact;
use Aurora\Modules\Core\Models\Channel;
use Aurora\Modules\Core\Models\Tenant;
use Aurora\Modules\Core\Models\User;
use Aurora\Modules\Core\Models\UserBlock;
use Aurora\Modules\CpanelIntegrator\Models\Alias;
use Aurora\Modules\MailDomains\Models\Domain;
use Aurora\Modules\Mail\Models\Identity;
use Aurora\Modules\Mail\Models\MailAccount;
use Aurora\Modules\Mail\Models\RefreshFolder;
use Aurora\Modules\Mail\Models\Sender;
use Aurora\Modules\Mail\Models\Server;
use Aurora\Modules\Mail\Models\SystemFolder;
use Aurora\Modules\MtaConnector\Models\Fetcher;
use Aurora\Modules\OAuthIntegratorWebclient\Models\OauthAccount;
use Aurora\Modules\StandardAuth\Models\Account as StandardAuthAccount;
use Aurora\Modules\TwoFactorAuth\Models\UsedDevice;
use Aurora\Modules\TwoFactorAuth\Models\WebAuthnKey;
use Aurora\System\Api;
use Aurora\System\Enums\LogLevel;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use \Illuminate\Database\Capsule\Manager as Capsule;

class EavToSqlCommandV2 extends Command
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

    protected function truncateIfExist($model)
    {
        if (class_exists($model)) {
            $model::truncate();
        }
        return true;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrateEntitiesList = [];
        $offset = 0;
        $time = time();
        $intProgress = 0;

        $filename = "Console/Commands/Migrations/logs/migration-" . $time . ".txt";
        $progressFilename = "Console/Commands/Migrations/logs/migration-progress.txt";
        $entitiesListFilename = "Console/Commands/Migrations/logs/migration-list.txt";
        $missedEntitiesFilename = "Console/Commands/Migrations/logs/migration-" . $time . "-missed-entities.txt";

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
            Api::Log('Going to wipe existing records', LogLevel::Full, $this->sFilePrefix);
            Capsule::connection()->statement("SET foreign_key_checks=0");
            $this->truncateIfExist(Tenant::class);
            $this->truncateIfExist(Channel::class);
            $this->truncateIfExist(User::class);
            $this->truncateIfExist(StandardAuthAccount::class);
            $this->truncateIfExist(Server::class);
            $this->truncateIfExist(MailAccount::class);
            $this->truncateIfExist(Identity::class);
            $this->truncateIfExist(Group::class);
            $this->truncateIfExist(Contact::class);
            $this->truncateIfExist(CTag::class);
            $this->truncateIfExist(Sender::class);
            $this->truncateIfExist(SystemFolder::class);
            $this->truncateIfExist(RefreshFolder::class);
            $this->truncateIfExist(Alias::class);
            $this->truncateIfExist(Fetcher::class);
            $this->truncateIfExist(OauthAccount::class);
            $this->truncateIfExist(UsedDevice::class);
            $this->truncateIfExist(WebAuthnKey::class);
            $this->truncateIfExist(UserBlock::class);
            $this->truncateIfExist(Domain::class);
            $this->truncateIfExist(GroupContact::class);
            Capsule::connection()->statement("SET foreign_key_checks=1");
        } else if ($migrateFile) {
            $fdListEntities = fopen($entitiesListFilename, 'a+') or die("cant create file");

            while (!feof($fdListEntities)) {
                $entityId = intval(htmlentities(fgets($fdListEntities)));
                if ($entityId) {
                    $migrateEntitiesList[] = $entityId;
                }
            }
            fclose($fdListEntities);

            $question = new ConfirmationQuestion("Do you really wish migrate " . $migrateEntitiesList[0] . ", " . $migrateEntitiesList[1] . ", ... , " . end($migrateEntitiesList) . " entities? (Y/N)", false);
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
            if (count($migrateEntitiesList) === 0) {
                $this->logger->error('Entities list is empty!');
                return false;
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

        $sql = "SELECT * FROM `" . $this->oP8Settings->DBPrefix . "eav_entities` GROUP BY id";
        $sqlIn = "";
        foreach ($migrateEntitiesList as $entityId) {
            $sqlIn .= $entityId . ',';
        }
        $sqlIn = substr($sqlIn, 0, -1);

        if ($migrateEntitiesList) {
            $sql = "SELECT * FROM `" . $this->oP8Settings->DBPrefix . "eav_entities` WHERE id IN ($sqlIn) GROUP BY id;";
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $oConnection = \Aurora\System\Api::GetConnection();
        $oConnection->execute($sql);

        $entities = [];
        while (false !== ($oRow = $oConnection->GetNextRecord())) {
            if ($oRow->id <= $intProgress) {
                continue;
            }
            $entities[] = $oRow;
        }

        $progressBar = new ProgressBar($output, count($entities));
        $progressBar->start();

        $result = $this->migrate($fdProgress, $fdErrors, $migrateEntitiesList, $entities, $progressBar, $fdMissedIds);

        $this->rewriteFile($fdErrors, $result['MissedEntities']);
        $this->rewriteFile($fdMissedIds, implode(PHP_EOL, $result['MissedIds']));

        fclose($fdMissedIds);
        fclose($fdErrors);
        fclose($fdProgress);
        return Command::SUCCESS;
    }

    private function migrate($fdProgress, $fdErrors, $migrateEntitiesList, $entities, $progressBar, $fdMissedIds)
    {
        $missedEntities = [];
        $missedIds = [];
        $contactsCache = [];
        $groupsCache = [];
        $migrateArray = [];

        $modelsMap = [
            'Aurora\Modules\Mail\Classes\Account' => 'Aurora\Modules\Mail\Models\MailAccount',
            'Aurora\Modules\OAuthIntegratorWebclient\Classes\Account' => 'Aurora\Modules\OAuthIntegratorWebclient\Models\OauthAccount'
        ];

        foreach ($entities as $i => $entity) {
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

            // if (!$laravelModel::where('Id', $entity->id)->first()) {
            $newRow = $laravelModel::create(array_merge($aItem, $migrateArray));
            // }

            $this->rewriteFile($fdProgress, $entity->id);
            $this->rewriteFile($fdErrors, json_encode($missedEntities));
            $this->rewriteFile($fdMissedIds, implode(PHP_EOL, $missedIds));
            $progressBar->advance();
        }
        $this->logger->info('Migration Completed!');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return ['MissedEntities' => json_encode($missedEntities), 'MissedIds' => $missedIds];
    }
}
