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
use Aurora\Modules\StandardAuth\Models\StandardAuthAccount;
use Aurora\Modules\TwoFactorAuth\Models\UsedDevice;
use Aurora\Modules\TwoFactorAuth\Models\WebAuthnKey;
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

class EavToSqlCommand extends Command
{
    private string $sFilePrefix = 'eav-to-sql-';

    private int $iOffset = 0;
    private int $iLimit = 1000;

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('migrate:eav-to-sql')
            ->setDescription('Migrate EAV data structure to SQL')
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'The EAV database connection to use')
            ->addOption('wipe', null, InputOption::VALUE_OPTIONAL, 'Wipe current database');
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
            Tenant::truncate();
            Channel::truncate();
            User::truncate();
            StandardAuthAccount::truncate();
            Server::truncate();
            MailAccount::truncate();
            Identity::truncate();
            Group::truncate();
            Contact::truncate();
            Ctag::truncate();
            Sender::truncate();
            SystemFolder::truncate();
            RefreshFolder::truncate();
            Alias::truncate();
            Fetcher::truncate();
            OauthAccount::truncate();
            UsedDevice::truncate();
            WebAuthnKey::truncate();
            UserBlock::truncate();
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
            foreach ($this->getObjects(EavDomain::class) as $eavDomain) {
//                Capsule::connection()->transaction(function () use ($eavDomain) {
                $this->migrate($progressBar, $eavDomain);
//                });
            };
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
        if ($eavDomain) {
            $eavTenants = $this->getObjects(EavTenant::class, 'EntityId', $eavDomain->get('TenantId'));
        } else {
            $eavTenants = $this->getObjects(EavTenant::class);
        }

        foreach ($eavTenants as $eavTenant) {
            $tenant = new Tenant($eavTenant
                ->except(['EntityId', 'IdTenant', 'IdChannel'])
                ->toArray()
            );
            $tenant->IsDisabled = !!$eavTenant->get('IsDisabled', false);
            $tenant->IsDefault = !!$eavTenant->get('IsDefault', false);
            $tenant->IsTrial = !!$eavTenant->get('IsTrial', false);

            if ($eavTenant->has('IdChannel')) {
                $eavChannel = $this->getObjects(EavChannel::class, 'EntityId', $eavTenant->get('IdChannel'))->first();
                $channel = new Channel($eavChannel
                    ->except(['EntityId'])
                    ->toArray()
                );
                $channel->save();
                Api::Log("Related channel {$eavChannel->get('EntityId')} with Tenant {$eavTenant->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);

                $tenant->IdChannel = $channel->Id;
            }
            $tenant->save();
            Api::Log("Tenant {$eavTenant->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);

            foreach ($this->getObjects(EavServer::class, 'TenantId', $eavTenant->get('EntityId')) as $eavServer) {
                $server = Server::firstOrCreate($eavServer
                    ->only((new Server())->getFillable())
                    ->merge([
                        'TenantId' => $tenant->Id
                    ])
                    ->toArray()
                );
                $server->save();
                Api::Log("Server {$eavTenant->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);

                $domain = Domain::firstOrCreate([
                    'TenantId' => $tenant->Id,
                    'MailServerId' => $server->Id
                ]);
                $domain->save();
                Api::Log("Domain {$eavDomain->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
            }

            foreach ($this->getObjects(EavUser::class, 'IdTenant', $eavTenant->get('EntityId')) as $eavUser) {
                $user = new User($eavUser
                    ->except(['EntityId'])
                    ->toArray()
                );
                $user->save();
                Api::Log("Related user {$eavUser->get('EntityId')} with Tenant {$eavTenant->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);

                $eavStandardAccount = $this->getObjects(EavStandardAuthAccount::class, 'IdUser', $eavUser->get('EntityId'));
                $standardAccount = new StandardAuthAccount($eavStandardAccount
                    ->only((new StandardAuthAccount())->getFillable())
                    ->toArray()
                );
                $standardAccount->IdUser = $user->Id;
                $standardAccount->save();
                Api::Log("Related StandardAccount {$eavStandardAccount->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);

                $account = null;
                foreach ($this->getObjects(EavAccount::class, 'IdUser', $eavUser->get('EntityId')) as $eavAccount) {

                    $eavServer = $this->getObjects(EavServer::class, 'EntityId', $eavAccount->get('ServerId'))->first();

                    if ($eavServer) {
                        $server = Server::firstOrCreate($eavServer
                            ->only((new Server())->getFillable())
                            ->merge([
                                'TenantId' => $tenant->Id
                            ])
                            ->toArray()
                        );
                        $server->save();
                        Api::Log("Related server {$eavServer->get('EntityId')} with Account {$eavAccount->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }

                    $account = new MailAccount($eavAccount
                        ->only((new MailAccount())->getFillable())
                        ->toArray()
                    );
                    $account->IdUser = $user->Id;
                    $account->ServerId = $server->Id;
                    $account->save();
                    Api::Log("Related MailAccount {$eavAccount->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                foreach ($this->getObjects(EavIdentity::class, 'IdUser', $eavUser->get('EntityId')) as $eavIdentity) {
                    $contact = new Identity($eavIdentity
                        ->only((new Identity())->getFillable())
                        ->toArray()
                    );
                    $contact->IdUser = $user->Id;
                    $contact->save();
                    Api::Log("Related Identity {$eavIdentity->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                foreach ($this->getObjects(EavGroup::class, 'IdUser', $eavUser->get('EntityId')) as $eavGroup) {
                    $contact = new Group($eavGroup
                        ->except(['EntityId', 'IdUser'])
                        ->toArray()
                    );
                    $contact->IdUser = $user->Id;
                    $contact->save();
                    Api::Log("Related group {$eavGroup->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                foreach ($this->getObjects(EavContact::class, 'IdUser', $eavUser->get('EntityId')) as $eavContact) {
                    $contact = new Contact($eavContact
                        ->except(['EntityId', 'IdTenant', 'IdUser'])
                        ->toArray()
                    );
                    $contact->IdTenant = $tenant->Id;
                    $contact->IdUser = $user->Id;
                    $contact->save();
                    Api::Log("Related contact {$eavContact->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                foreach ($this->getObjects(EavCTag::class, 'IdUser', $eavUser->get('EntityId')) as $eavCTag) {
                    $cTag = new Ctag($eavCTag
                        ->only((new Ctag())->getFillable())
                        ->toArray()
                    );
                    $cTag->IdUser = $user->Id;
                    $cTag->save();
                    Api::Log("Related CTag {$eavCTag->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                }

                foreach ($this->getObjects(EavGroupContact::class) as $eavGroupContactEntity) {

                    $eavGroupUser = $this->getObjects(EavUser::class, 'EntityId', $eavGroupContactEntity->get('IdUser'))->first();
                    if ($eavGroupUser) {
                        $eavGroupEntity = $this->getObjects(EavGroup::class, 'UUID', $eavGroupContactEntity->get('GroupUUID'))->first();
                        if ($eavGroupEntity) {
                            $eavGroupContact = $this->getObjects(EavContact::class, 'UUID', $eavGroupContactEntity->get('ContactUUID'))->first();
                            if ($eavGroupContact) {
                                $contactUser = User::firstOrCreate($eavGroupUser
                                    ->only((new User())->getFillable())
                                    ->toArray()
                                );
                                $contactUser->save();

                                $contactGroup = Contact::firstOrCreate($eavGroupContact
                                    ->only((new Contact())->getFillable())
                                    ->merge([
                                        'IdTenant' => $tenant->Id,
                                        'IdUser' => $contactUser->Id
                                    ])
                                    ->toArray()
                                );
                                $contactGroup->save();

                                $groupEntity = Group::firstOrCreate($eavGroupEntity
                                    ->only((new Group())->getFillable())
                                    ->merge([
                                        'IdUser' => $contactUser->Id
                                    ])
                                    ->toArray()
                                );

                                $groupEntity->Contacts()->attach($contactGroup);
                                $groupEntity->save();
                            }
                        }
                    }

                }

                foreach ($this->getObjects(EavSender::class, 'IdUser', $eavUser->get('EntityId')) as $eavSender) {
                    $sender = new Sender($eavSender
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
                        $sender = new SystemFolder($eavSystemFolder
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
                        $sender = new RefreshFolder($eavRefreshFolder
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
                        $sender = new Alias($eavCpanelAlias
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
                        $sender = new Fetcher($eavFetcher
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
                        $sender = new OauthAccount($eavOauthAccount
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

                    foreach ($this->getObjects(EavUsedDevice::class, 'IdUser', $eavUser->get('EntityId')) as $eavUsedDevice) {
                        $sender = new UsedDevice($eavUsedDevice
                            ->only((new UsedDevice())->getFillable())
                            ->merge([
                                'UserId' => $user->Id
                            ])
                            ->toArray()
                        );
                        $sender->save();
                        Api::Log("Related UsedDevice {$eavUsedDevice->get('EntityId')} with User {$eavUser->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
                    }

                    foreach ($this->getObjects(EavWebAuthnKey::class, 'IdUser', $eavUser->get('EntityId')) as $eavWebAuthnKey) {
                        $sender = new WebAuthnKey($eavWebAuthnKey
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
            $sender = new UserBlock($eavUserBlock
                ->only((new UserBlock())->getFillable())
                ->toArray()
            );
            $sender->save();
            Api::Log("UserBlock {$eavUserBlock->get('EntityId')} successfully migrated", LogLevel::Full, $this->sFilePrefix);
        }

    }

}
