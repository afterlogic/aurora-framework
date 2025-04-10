<?php

namespace Aurora\System\Console\Commands;

use Aurora\System\Api;
use Aurora\System\Console\Commands\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Log\LogLevel;

class OrphansCommand extends BaseCommand
{
    /**
     * @var ConsoleLogger
     */
    private $logger = false;

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('orphans')
            ->setDescription('Collect orphan entries')
            ->addOption('remove', 'r', InputOption::VALUE_NONE, 'Remove orphan entries from DB.')
        ;
    }

    protected function rewriteFile($fd, $str)
    {
        ftruncate($fd, 0);
        fseek($fd, 0, SEEK_END);
        fwrite($fd, $str);
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
            $sOutput .= PHP_EOL . "\t\t\"" . implode('","', $value) . "\"";
            $sOutput .= PHP_EOL . "\t]";
        }
        $sOutput .= PHP_EOL . '}';
        $sOutput = str_replace('\\', '\\\\', $sOutput);

        return $sOutput;
    }

    protected function checkOrphans($fdEntities, $input, $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Remove these orphan entries? [yes]', true);

        $aOrphansEntities = [];
        $aModels = $this->getAllModels();
        foreach ($aModels as $moduleName => $moduleModels) {
            foreach ($moduleModels as $modelPath) {
                $model = str_replace('/', DIRECTORY_SEPARATOR, $modelPath);
                $model = str_replace('\\', DIRECTORY_SEPARATOR, $model);
                $model = explode(DIRECTORY_SEPARATOR, $model);

                while ($model[0] !== 'modules') {
                    array_shift($model);
                }
                $model[0] = 'Modules';
                array_unshift($model, "Aurora");
                $model = implode('\\', $model);

                $modelObject = new $model();
                $primaryKey = $modelObject->getKeyName(); // get primary key column name

                // This block is required for the work with custom connection to DB which is defined in MtaConnector module
                $sConnectionName = $modelObject->getConnectionName();
                if ($sConnectionName) {
                    $sModuleClassName = '\\Aurora\\Modules\\' . $moduleName . '\\Module';
                    $sModelPath = Api::GetModuleManager()->GetModulePath($moduleName);
                    $oModule = new $sModuleClassName($sModelPath);
                    $oModule->addDbConnection();
                }

                $this->logger->info('Checking ' . $model::query()->getQuery()->from . ' table.');

                $checkOrphan = $modelObject->getOrphanIds();
                switch($checkOrphan['status']) {
                    case 0:
                        $this->logger->info($checkOrphan['message']);
                        break;
                    case 1:
                        $aOrphansEntities[$model] = array_values($checkOrphan['orphansIds']);
                        sort($aOrphansEntities[$model]);
                        if ($input->getOption('remove') && !empty($aOrphansEntities[$model])) {
                            $this->logger->error($checkOrphan['message']);
                            $bRemove = $helper->ask($input, $output, $question);

                            if ($bRemove) {
                                $modelObject::whereIn($primaryKey, $aOrphansEntities[$model])->delete();
                                $this->logger->warning('Orphan entries was removed.');
                            } else {
                                $this->logger->warning('Orphan entries removing was skipped.');
                            }
                        } else {
                            $this->logger->error($checkOrphan['message']);
                        }
                        break;
                    default:
                        $this->logger->info($checkOrphan['message']);
                        break;
                }
                echo PHP_EOL;
            }
        }
        return $aOrphansEntities;
    }

    protected function checkFileOrphans($fdEntities, $input, $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Remove files of the orphan users? [yes]', true);

        $dirFiles = \Aurora\System\Api::DataPath() . "/files";
        $dirPersonalFiles = $dirFiles . "/private";
        $dirOrphanFiles = $dirFiles . "/orphan_user_files";
        $aOrphansEntities = [];

        if (is_dir($dirPersonalFiles)) {
            $this->logger->info("Checking Personal files.");

            $dirs = array_diff(scandir($dirPersonalFiles), array('..', '.'));

            $orphanUUIDs = array_values(array_diff($dirs, \Aurora\Modules\Core\Models\User::query()->pluck('UUID')->toArray()));

            if (!empty($orphanUUIDs)) {
                $aOrphansEntities['PersonalFiles'] = $orphanUUIDs;

                $this->logger->error("Personal files orphans were found: " . count($orphanUUIDs));

                if ($input->getOption('remove')) {
                    $bRemove = $helper->ask($input, $output, $question);

                    if ($bRemove) {
                        if (!is_dir($dirOrphanFiles)) {
                            mkdir($dirOrphanFiles);
                        }

                        foreach ($orphanUUIDs as $orphanUUID) {
                            rename($dirPersonalFiles . "/" . $orphanUUID, $dirOrphanFiles . "/" . $orphanUUID);
                        }

                        $this->logger->warning('Orphan user files were moved to ' . $dirOrphanFiles . '.');
                    } else {
                        $this->logger->warning('Orphan user files removing was skipped.');
                    }
                }
            } else {
                $this->logger->info("Personal files orphans were not found.");
            }
        }
        return $aOrphansEntities;
    }

    protected function checkDavOrphans($fdEntities, $input, $output)
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Remove DAV objects of the orphan users? [yes]', true);

        $dbPrefix = Api::GetSettings()->DBPrefix;
        $aOrphansEntities = [];
        if (Capsule::schema()->hasTable('adav_calendarinstances')) {
            echo PHP_EOL;
            $this->logger->info("Checking DAV calendar.");

            $rows = Capsule::connection()->select('SELECT aci.calendarid, aci.id FROM ' . $dbPrefix . 'adav_calendarinstances as aci
                WHERE SUBSTRING(principaluri, 12) NOT IN (SELECT PublicId FROM ' . $dbPrefix . 'core_users) AND principaluri NOT LIKE \'%_dav_tenant_user@%\'');

            if (count($rows) > 0) {
                $this->logger->error("DAV calendars orphans were found: " . count($rows));

                $aOrphansEntities['DAV-Calendars'] = array_map(function ($row) {
                    return $row->id;
                }, $rows);

                if ($input->getOption('remove')) {
                    $bRemove = $helper->ask($input, $output, $question);

                    if ($bRemove) {
                        foreach ($rows as $row) {
                            \Afterlogic\DAV\Backend::Caldav()->deleteCalendar([$row->calendarid, $row->id]);
                        }
                    }
                }
            } else {
                $this->logger->info("DAV calendars orphans were not found.");
            }
        }

        if (Capsule::schema()->hasTable('adav_addressbooks')) {
            echo PHP_EOL;
            $this->logger->info("Checking DAV addressbooks.");

            $rows = Capsule::connection()->select('SELECT id FROM ' . $dbPrefix . 'adav_addressbooks WHERE (SUBSTRING(principaluri, 12) NOT IN (SELECT PublicId FROM ' . $dbPrefix . 'core_users) AND principaluri NOT LIKE \'%_dav_tenant_user@%\') OR ISNULL(principaluri)');

            if (count($rows) > 0) {
                $this->logger->error("DAV addressbooks orphans were found: " . count($rows));

                $aOrphansEntities['DAV-Addressbooks'] = array_map(function ($row) {
                    return $row->id;
                }, $rows);

                if ($input->getOption('remove')) {
                    $bRemove = $helper->ask($input, $output, $question);

                    if ($bRemove) {
                        foreach ($rows as $row) {
                            \Afterlogic\DAV\Backend::Carddav()->deleteAddressBook($row->id);
                        }
                    }
                }
            } else {
                $this->logger->info("DAV addressbooks orphans were not found.");
            }
        }

        return $aOrphansEntities;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $verbosityLevelMap = [
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO   => OutputInterface::VERBOSITY_NORMAL,
        ];

        $dirName = \Aurora\System\Logger::GetLogFileDir() . "/orphans-logs";
        $entitiesFileName = $dirName . "/orphans_" . date('Y-m-d_H-i-s') . ".json";
        $orphansEntities = [];

        $dirname = dirname($entitiesFileName);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }

        $fdEntities = fopen($entitiesFileName, 'a+') or die("Can't create migration-progress.txt file");

        $this->logger = new ConsoleLogger($output, $verbosityLevelMap);
        $orphansEntities = array_merge(
            $orphansEntities,
            $this->checkOrphans($fdEntities, $input, $output)
        );
        $orphansEntities = array_merge(
            $orphansEntities,
            $this->checkFileOrphans($fdEntities, $input, $output)
        );
        $orphansEntities = array_merge(
            $orphansEntities,
            $this->checkDavOrphans($fdEntities, $input, $output)
        );

        if (count($orphansEntities) > 0) {
            $this->rewriteFile($fdEntities, $this->jsonPretify($orphansEntities));
        }

        return Command::SUCCESS;
    }
}
