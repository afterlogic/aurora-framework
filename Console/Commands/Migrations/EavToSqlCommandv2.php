<?php

namespace Aurora\System\Console\Commands\Migrations;

use Aurora\Modules\Contacts\Models\Contact;
use Aurora\Modules\Contacts\Models\Ctag;
use Aurora\Modules\Contacts\Models\Group;
use Aurora\Modules\Contacts\Models\GroupContact;
use Aurora\Modules\Core\Models\Channel;
use Aurora\Modules\Core\Models\Tenant;
use Aurora\Modules\Core\Models\User;
use Aurora\Modules\Core\Models\UserBlock;
use Aurora\Modules\CpanelIntegrator\Models\Alias;
use Aurora\Modules\MailDomains\Classes\Domain as EavDomain;
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
use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
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
            ->addOption('without-mutual-tables', null, InputOption::VALUE_OPTIONAL, 'Didnt migrate mutual tables (awm tables, min_hashes, activity_history)')
            ->addOption('revert', null, InputOption::VALUE_OPTIONAL, 'Revert general tables');
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
                switch ($extendedProp) {
                    case 'MailDomains::DomainId':
                        $eavDomainId = $object->get($extendedProp);
                        $eavDomain = $this->getObjects(EavDomain::class, 'EntityId', $eavDomainId)->first();
                        $newDomain = Domain::where('Name', $eavDomain->get('Name'))->first();
                        $extendedProps[$extendedProp] = $newDomain->Id;
                        break;
                    default:
                        $extendedProps[$extendedProp] = $object->get($extendedProp);
                        break;
                }
            }
        }
        return $extendedProps;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $verbosityLevelMap = array(
            'notice' => OutputInterface::VERBOSITY_NORMAL,
            'info' => OutputInterface::VERBOSITY_NORMAL,
        );
        $this->logger = new ConsoleLogger($output, $verbosityLevelMap);
        $this->oP8Settings = \Aurora\System\Api::GetSettings();
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you really wish to run this command? (Y/N)', false);

        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $wipe = $input->getOption('wipe');
        if ($wipe) {
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
            $this->truncateIfExist(Ctag::class);
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
        }

        // $totalUsers = (new \Aurora\System\EAV\Query(EavUser::class))
        //     ->offset($this->iOffset)
        //     ->limit($this->iLimit)
        //     ->count()
        //     ->exec();

        // $progressBar = new ProgressBar($output, $totalUsers);
        // $progressBar->start();
        // $this->migrate($progressBar);
        $this->migrate();

        return Command::SUCCESS;
    }

    /**
     * @param $sObjectType
     * @param string $sSearchField
     * @param string $sSearchText
     * @return Collection|
     */
    private function getObjects($sObjectType, $sSearchField = '', $sSearchText = '')
    {
        if (!class_exists($sObjectType)) {
            return collect([]);
        }
        $writeln = "Select {$sObjectType}";
        if ($sSearchField && $sSearchText) {
            $writeln .= " by {$sSearchField} = {$sSearchText}";
        }
        Api::Log($writeln, LogLevel::Full, $this->sFilePrefix);

        $oEntity = new $sObjectType('Core');

        $aFilters = array();
        if (!empty($sSearchField)) {
            switch ($oEntity->getType($sSearchField)) {
                case 'string':
                    $aFilters = [$sSearchField => ['%' . (string) $sSearchText . '%', 'LIKE']];
                    break;
                case 'int':
                    $aFilters = [$sSearchField => [(int) $sSearchText, '=']];
                    break;
                case 'bigint':
                    $aFilters = [$sSearchField => [$sSearchText, '=']];
                    break;
                case 'bool':
                    $aFilters = [$sSearchField => [(bool) $sSearchText, '=']];
                    break;
            }
        }

        $aItems = collect(
            (new \Aurora\System\EAV\Query($sObjectType))
                ->where($aFilters)
                ->offset($this->iOffset)
                ->limit($this->iLimit)
                ->asArray()
                ->exec()
        );

        Api::Log("Found {$aItems->count()} records", LogLevel::Full, $this->sFilePrefix);
        return $aItems->map(function ($item) {
            return collect($item);
        });
    }

    private function checkExistTable($oConnection, $sTableName)
    {
        $this->logger->info("migrating " . $sTableName . " table...");
        $oConnection->execute("SHOW TABLES LIKE '" . $sTableName . "';");
        $result = !!$oConnection->GetNextRecord();
        if (!$result) {
            $this->logger->warning($sTableName . " table does not exist");
        }
        return $result;
    }

    private function migrate()
    {
        $oConnection = \Aurora\System\Api::GetConnection();
        $oConnection->execute('SELECT * FROM `' . $this->oP8Settings->DBPrefix . 'eav_entities`;');
        $entities=[];
        while (false !== ($oRow = $oConnection->GetNextRecord())) {
            var_dump($oRow->id);
            $entities[] = $oRow;
        }

        foreach($entities as $entity){
            var_dump($entity->id);
            $aItem = collect(
                (new \Aurora\System\EAV\Query($entity->entity_type))
                    ->where(['EntityId' => $entity->id])
                    ->asArray()
                    ->exec()
            )->first();
            $laravelModel = str_replace('Classes', 'Models', $entity->entity_type);
            $migrateArray = ['Id' => $entity->id];
            if ($entity->entity_type === 'Aurora\Modules\Contacts\Classes\GroupContact') {
                $contact = collect(
                    (new \Aurora\System\EAV\Query('Aurora\Modules\Contacts\Classes\Contact'))
                        ->where(['UUID' => $aItem['ContactUUID']])
                        ->asArray()
                        ->exec()
                )->first();
                $group = collect(
                    (new \Aurora\System\EAV\Query('Aurora\Modules\Contacts\Classes\Group'))
                        ->where(['UUID' => $aItem['GroupUUID']])
                        ->asArray()
                        ->exec()
                )->first();
                $migrateArray['ContactId'] = $contact['EntityId'];
                $migrateArray['GroupId'] = $group['EntityId'];
            }
            $newRow = $laravelModel::create(array_merge($aItem, $migrateArray));
        }
    }
}
