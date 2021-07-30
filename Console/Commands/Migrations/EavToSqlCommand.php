<?php

namespace Aurora\System\Console\Commands\Migrations;

use Aurora\Modules\Contacts\Models\Contact;
use Aurora\Modules\Contacts\Models\Ctag;
use Aurora\Modules\Contacts\Models\Group;
use Aurora\Modules\Core\Models\Channel;
use Aurora\Modules\Core\Models\Tenant;
use Aurora\Modules\Core\Models\User;
use Aurora\Modules\Core\Models\UserBlock;
use Aurora\Modules\CpanelIntegrator\Models\Alias;
use Aurora\Modules\Mail\Models\Identity;
use Aurora\Modules\Mail\Models\MailAccount;
use Aurora\Modules\Mail\Models\RefreshFolder;
use Aurora\Modules\Mail\Models\Sender;
use Aurora\Modules\Mail\Models\Server;
use Aurora\Modules\Mail\Models\SystemFolder;
use Aurora\Modules\MailDomains\Models\Domain;
use Aurora\Modules\MtaConnector\Models\Fetcher;
use Aurora\Modules\OAuthIntegratorWebclient\Models\OauthAccount;
use Aurora\Modules\StandardAuth\Models\Account as StandardAuthAccount;
use Aurora\Modules\TwoFactorAuth\Models\UsedDevice;
use Aurora\Modules\TwoFactorAuth\Models\WebAuthnKey;
use Aurora\Modules\Contacts\Models\GroupContact;
use Aurora\System\Api;
use Aurora\System\Enums\LogLevel;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use \Illuminate\Database\Capsule\Manager as Capsule;

use Aurora\Modules\Core\Classes\Tenant as EavTenant;
use Aurora\Modules\Core\Classes\Channel as EavChannel;
use Aurora\Modules\Core\Classes\User as EavUser;
use Aurora\Modules\Contacts\Classes\Contact as EavContact;
use Aurora\Modules\Contacts\Classes\Group as EavGroup;
use Aurora\Modules\Contacts\Classes\GroupContact as EavGroupContact;
use Aurora\Modules\Mail\Classes\Server as EavServer;
use Aurora\Modules\MailDomains\Classes\Domain as EavDomain;
use Aurora\Modules\Mail\Classes\Account as EavAccount;
use Aurora\Modules\Contacts\Classes\CTag as EavCTag;
use Aurora\Modules\Mail\Classes\Sender as EavSender;
use Aurora\Modules\Mail\Classes\Identity as EavIdentity;
use Aurora\Modules\Mail\Classes\SystemFolder as EavSystemFolder;
use Aurora\Modules\Mail\Classes\RefreshFolder as EavRefreshFolder;
use Aurora\Modules\Core\Classes\UserBlock as EavUserBlock;
use Aurora\Modules\StandardAuth\Classes\Account as EavStandardAuthAccount;
use Aurora\Modules\CpanelIntegrator\Classes\Alias as EavCpanelAlias;
use Aurora\Modules\MtaConnector\Classes\Fetcher as EavFetcher;
use Aurora\Modules\OAuthIntegratorWebclient\Classes\Account as EavOauthAccount;
use Aurora\Modules\TwoFactorAuth\Classes\UsedDevice as EavUsedDevice;
use Aurora\Modules\TwoFactorAuth\Classes\WebAuthnKey as EavWebAuthnKey;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class EavToSqlCommand extends Command
{
    private $sFilePrefix = 'eav-to-sql-';

    private $iOffset = 0;
    private $iLimit = 1000;

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
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'The EAV database connection to use')
            ->addOption('wipe', null, InputOption::VALUE_OPTIONAL, 'Wipe current database');
    }

    protected function truncateIfExist($model)
    {
        if (class_exists($model)) {
            $model::truncate();
        }
        return true;
    }

    protected function getProperties($class, $object){
        $extendedPropsUser = \Aurora\System\ObjectExtender::getInstance()->getExtendedProps($class);
        $extendedProps = [];
        foreach(array_keys($extendedPropsUser) as $extendedProp){
            if($object->get($extendedProp)){
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
        $eavDomains = $this->getObjects(EavDomain::class);
        $totalUsers = (new \Aurora\System\EAV\Query(EavUser::class))
            ->offset($this->iOffset)
            ->limit($this->iLimit)
            ->count()
            ->exec();

        $progressBar = new ProgressBar($output, $totalUsers);
        $progressBar->start();

        if ($eavDomains->isEmpty()) {
            //            Capsule::connection()->transaction(function () {
            $this->migrate($progressBar);
            //            });
        } else {
            // dd($this->getObjects(EavDomain::class));
            foreach ($this->getObjects(EavDomain::class) as $eavDomain) {
                //                Capsule::connection()->transaction(function () use ($eavDomain) {
                $this->migrate($progressBar, $eavDomain);
                //                });
            };
            // $this->migrate($progressBar);
        }

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
                    $aFilters = [$sSearchField => ['%' . (string)$sSearchText . '%', 'LIKE']];
                    break;
                case 'int':
                    $aFilters = [$sSearchField => [(int)$sSearchText, '=']];
                    break;
                case 'bigint':
                    $aFilters = [$sSearchField => [$sSearchText, '=']];
                    break;
                case 'bool':
                    $aFilters = [$sSearchField => [(bool)$sSearchText, '=']];
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

    private function migrate($progressBar, $eavDomain = null)
    {
        $eavTenants = $this->getObjects(EavTenant::class);
        foreach ($eavTenants as $eavTenant) {
            if($eavDomain){
                if ($eavTenant->get('EntityId') !== $eavDomain->get('TenantId')) {
                    continue;
                }
            }
            $tenant = Tenant::where('Name', $eavTenant->get('Name'))->first();
            if (!$tenant) {
                $tenant = new Tenant(
                    $eavTenant
                        ->except(['EntityId', 'IdTenant', 'IdChannel'])
                        ->toArray()
                );
                $tenant->Properties = $this->getProperties(EavTenant::class, $eavTenant);
                $tenant->IsDisabled = !!$eavTenant->get('IsDisabled', false);
                $tenant->IsDefault = !!$eavTenant->get('IsDefault', false);
                $tenant->IsTrial = !!$eavTenant->get('IsTrial', false);

                if ($eavTenant->has('IdChannel')) {
                    $eavChannel = $this->getObjects(EavChannel::class, 'EntityId', $eavTenant->get('IdChannel'))->first();
                    $channel = new Channel(
                        $eavChannel
                            ->except(['EntityId'])
                            ->toArray()
                    );
                    $channel->save();
                    Api::Log("Related channel {$eavChannel->get('EntityId')} with Tenant {$eavTenant->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);

                    $tenant->IdChannel = $channel->Id;
                }
                $tenant->save();
            }

            Api::Log("Tenant {$eavTenant->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);


            $eavServersWithGlobalTenants = [];
            $globalServers = $this->getObjects(EavServer::class, 'TenantId', 0);
            $localServers = $this->getObjects(EavServer::class, 'TenantId', $eavTenant->get('EntityId'));

            foreach($globalServers as $globalServer){
                $eavServersWithGlobalTenants[] = $globalServer;
            }

            foreach($localServers as $localTenant){
                $eavServersWithGlobalTenants[] = $localTenant;
            }


            foreach ($eavServersWithGlobalTenants as $eavServer) {
                $tenantForServer = $this->getObjects(EavTenant::class, 'EntityId', $eavServer->get('TenantId'))->first();
                $tenantId = $tenantForServer ? Tenant::where('Name', $tenantForServer->get('Name'))->first()->Id : 0;
                $server = Server::firstOrCreate(
                    $eavServer
                        ->only((new Server())->getFillable())
                        ->merge([
                            'TenantId' => $tenantId
                        ])
                        ->toArray()
                );
                Api::Log("Server {$eavTenant->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                foreach ($this->getObjects(EavDomain::class, 'MailServerId', $eavServer->get('EntityId')) as $serverEavDomain) {
                    if ($eavTenant->get('EntityId') === $serverEavDomain->get('TenantId')) {
                        $domain = Domain::firstOrCreate([
                            'Name' => $serverEavDomain->get('Name'),
                            'TenantId' => $tenant->Id,
                            'MailServerId' => $server->Id
                        ]);
                    }
                    Api::Log("Domain {$serverEavDomain->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }
            }

            foreach ($this->getObjects(EavUser::class, 'IdTenant', $eavTenant->get('EntityId')) as $eavUser) {
                if($eavDomain){
                    if ($eavUser->get('MailDomains::DomainId') !== $eavDomain->get('EntityId')) {
                        continue;
                    }
                }
                
                $user = User::firstOrCreate(
                    $eavUser
                        ->only((new User())->getFillable())
                        ->except(['EntityId','IdTenant'])
                        ->toArray()
                );
                $user->Properties = $this->getProperties(EavUser::class, $eavUser);
                $user->IdTenant = $tenant->Id;
                $user->save();
                Api::Log("Related user {$eavUser->get('EntityId')} with Tenant {$eavTenant->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                if (class_exists(StandardAuthAccount::class)) {
                    $eavStandardAccount = $this->getObjects(EavStandardAuthAccount::class, 'IdUser', $eavUser->get('EntityId'));
                    $standardAccount = new StandardAuthAccount(
                        $eavStandardAccount
                            ->only((new StandardAuthAccount())->getFillable())
                            ->toArray()
                    );
                    $oldStandardAccount = array_pop($eavStandardAccount
                        ->except(['EntityId'])
                        ->toArray());
                    $standardAccount->IdUser = $user->Id;
                    $standardAccount->Login = $oldStandardAccount['Login'];
                    $standardAccount->Password = $oldStandardAccount['Password'];
                    $standardAccount->save();
                    Api::Log("Related StandardAccount {$eavStandardAccount->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                $account = null;
                foreach ($this->getObjects(EavAccount::class, 'IdUser', $eavUser->get('EntityId')) as $eavAccount) {

                    $eavServer = $this->getObjects(EavServer::class, 'EntityId', $eavAccount->get('ServerId'))->first();
                    if ($eavServer) {
                        $server = Server::firstOrCreate(
                            $eavServer
                                ->only((new Server())->getFillable())
                                ->toArray()
                        );
                        Api::Log("Related server {$eavServer->get('EntityId')} with Account {$eavAccount->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }

                    $account = new MailAccount(
                        $eavAccount
                            ->only((new MailAccount())->getFillable())
                            ->toArray()
                    );

                    $eavAccountObject = array_pop((new \Aurora\System\EAV\Query(EavAccount::class))
                    ->where(['EntityId' => [$eavAccount->get('EntityId'), '=']])
                    ->exec());

                    $account->IdUser = $user->Id;
                    $account->ServerId = $server->Id;
                    $account->IncomingPassword = $eavAccountObject->getPassword();
                    $account->save();
                    Api::Log("Related MailAccount {$eavAccount->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                foreach ($this->getObjects(EavIdentity::class, 'IdUser', $eavUser->get('EntityId')) as $eavIdentity) {
                    $contact = new Identity(
                        $eavIdentity
                            ->only((new Identity())->getFillable())
                            ->toArray()
                    );
                    $eavIdentityAccount = $this->getObjects(EavAccount::class, 'EntityId', $eavIdentity->get('IdAccount'))->first();
                    $identityAccount = MailAccount::where('IncomingLogin', $eavIdentityAccount->get('IncomingLogin'))->first();
                    $contact->IdAccount = $identityAccount->Id;
                    $contact->IdUser = $user->Id;
                    $contact->save();
                    Api::Log("Related Identity {$eavIdentity->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }
                foreach ($this->getObjects(EavGroup::class, 'IdUser', $eavUser->get('EntityId')) as $eavGroup) {
                    $contact = new Group(
                        $eavGroup
                            ->except(['EntityId', 'IdUser'])
                            ->toArray()
                    );
                    $contact->IdUser = $user->Id;
                    $contact->save();
                    Api::Log("Related group {$eavGroup->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                foreach ($this->getObjects(EavContact::class, 'IdUser', $eavUser->get('EntityId')) as $eavContact) {
                    $contact = Contact::firstOrCreate(
                        $eavContact
                            ->only((new Contact())->getFillable())
                            ->except(['EntityId', 'IdTenant', 'IdUser'])
                            ->toArray()
                    );
                    $contact->IdTenant = $tenant->Id;
                    $contact->IdUser = $user->Id;
                    $contact->save();
                    Api::Log("Related contact {$eavContact->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                $eavCTags = $this->getObjects(EavCTag::class, 'UserId', $eavUser->get('EntityId'));
                if(!$eavCTags[0]){
                    $eavCTags = $this->getObjects(EavCTag::class, 'UserId', $eavTenant->get('EntityId'));
                }
                foreach ($eavCTags as $eavCTag) {
                    $cTag = Ctag::firstOrNew(
                        $eavCTag
                            ->only((new Ctag())->getFillable())
                            ->toArray()
                    );
                    $cTag->UserId = $user->Id;
                    $cTag->save();
                    Api::Log("Related CTag {$eavCTag->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                foreach ($this->getObjects(EavGroupContact::class) as $eavGroupContactEntity) {
                    $eavContact = $this->getObjects(EavContact::class, 'UUID', $eavGroupContactEntity->get('ContactUUID'))->first();
                    if ($eavContact->get('IdTenant') === $eavTenant->get('EntityId')) {
                        $eavGroupUser = $this->getObjects(EavUser::class, 'EntityId', $eavContact->get('IdUser'))->first();
                        if ($eavGroupUser) {
                            $eavGroupEntity = $this->getObjects(EavGroup::class, 'UUID', $eavGroupContactEntity->get('GroupUUID'))->first();
                            if ($eavGroupEntity) {
                                $eavGroupContact = $this->getObjects(EavContact::class, 'UUID', $eavGroupContactEntity->get('ContactUUID'))->first();
                                if ($eavGroupContact) {
                                    $prepareGroupUser = $eavGroupUser
                                        ->only((new User())->getFillable())
                                        ->toArray();
                                    $prepareGroupUser['IdTenant'] = $tenant->Id;
                                    $contactUser = User::firstOrCreate($prepareGroupUser);
                                    $contactGroup = Contact::firstOrCreate(
                                        $eavGroupContact
                                            ->only((new Contact())->getFillable())
                                            ->merge([
                                                'IdTenant' => $tenant->Id,
                                                'IdUser' => $contactUser->Id
                                            ])
                                            ->toArray()
                                    );

                                    $groupEntity = Group::firstOrCreate(
                                        $eavGroupEntity
                                            ->only((new Group())->getFillable())
                                            ->except([
                                                'IdUser'
                                            ])
                                            ->toArray()
                                    );
                                    $groupEntity->Contacts()->attach($contactGroup);
                                    $groupEntity->save();
                                }
                            }
                        }
                    }
                }

                foreach ($this->getObjects(EavSender::class, 'IdUser', $eavUser->get('EntityId')) as $eavSender) {
                    $sender = new Sender(
                        $eavSender
                            ->only((new Sender())->getFillable())
                            ->merge([
                                'IdUser' => $user->Id
                            ])
                            ->toArray()
                    );
                    $sender->save();
                    Api::Log("Related Sender {$eavSender->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                if ($account instanceof MailAccount && isset($eavAccount)) {
                    foreach ($this->getObjects(EavSystemFolder::class, 'IdAccount', $eavAccount->get('EntityId')) as $eavSystemFolder) {
                        $sender = new SystemFolder(
                            $eavSystemFolder
                                ->only((new SystemFolder())->getFillable())
                                ->merge([
                                    'IdAccount' => $account->Id
                                ])
                                ->toArray()
                        );
                        $sender->save();
                        Api::Log("Related SystemFolder {$eavSystemFolder->get('EntityId')} with Account {$eavAccount->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }

                    foreach ($this->getObjects(EavRefreshFolder::class, 'IdAccount', $eavAccount->get('EntityId')) as $eavRefreshFolder) {
                        $sender = new RefreshFolder(
                            $eavRefreshFolder
                                ->only((new RefreshFolder())->getFillable())
                                ->merge([
                                    'IdAccount' => $account->Id
                                ])
                                ->toArray()
                        );
                        $sender->save();
                        Api::Log("Related RefreshFolder {$eavRefreshFolder->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }

                    foreach ($this->getObjects(EavCpanelAlias::class, 'IdUser', $eavUser->get('EntityId')) as $eavCpanelAlias) {
                        $sender = new Alias(
                            $eavCpanelAlias
                                ->only((new Alias())->getFillable())
                                ->merge([
                                    'IdAccount' => $account->Id,
                                    'IdUser' => $user->Id
                                ])
                                ->toArray()
                        );
                        $sender->save();
                        Api::Log("Related CpanelAlias {$eavCpanelAlias->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }

                    foreach ($this->getObjects(EavFetcher::class, 'IdUser', $eavUser->get('EntityId')) as $eavFetcher) {
                        $sender = new Fetcher(
                            $eavFetcher
                                ->only((new Fetcher())->getFillable())
                                ->merge([
                                    'IdAccount' => $account->Id,
                                    'IdUser' => $user->Id
                                ])
                                ->toArray()
                        );
                        $sender->save();
                        Api::Log("Related Fetcher {$eavFetcher->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }

                    foreach ($this->getObjects(EavOauthAccount::class, 'IdUser', $eavUser->get('EntityId')) as $eavOauthAccount) {
                        $sender = new OauthAccount(
                            $eavOauthAccount
                                ->only((new OauthAccount())->getFillable())
                                ->merge([
                                    'IdAccount' => $account->Id,
                                    'IdUser' => $user->Id
                                ])
                                ->toArray()
                        );
                        $sender->save();
                        Api::Log("Related OauthAccount {$eavOauthAccount->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }

                    foreach ($this->getObjects(EavUsedDevice::class, 'UserId', $eavUser->get('EntityId')) as $eavUsedDevice) {
                        $sender = new UsedDevice(
                            $eavUsedDevice
                                ->only((new UsedDevice())->getFillable())
                                ->merge([
                                    'UserId' => $user->Id
                                ])
                                ->toArray()
                        );
                        $sender->save();
                        Api::Log("Related UsedDevice {$eavUsedDevice->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }

                    foreach ($this->getObjects(EavWebAuthnKey::class, 'UserId', $eavUser->get('EntityId')) as $eavWebAuthnKey) {
                        $sender = new WebAuthnKey(
                            $eavWebAuthnKey
                                ->only((new WebAuthnKey())->getFillable())
                                ->merge([
                                    'UserId' => $user->Id
                                ])
                                ->toArray()
                        );
                        $sender->save();
                        Api::Log("Related WebAuthnKey {$eavWebAuthnKey->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }
                }

                $progressBar->advance();
            }
        }

        foreach ($this->getObjects(EavUserBlock::class) as $eavUserBlock) {
            $sender = new UserBlock(
                $eavUserBlock
                    ->only((new UserBlock())->getFillable())
                    ->toArray()
            );
            $sender->save();
            Api::Log("UserBlock {$eavUserBlock->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
        }
    }
}
