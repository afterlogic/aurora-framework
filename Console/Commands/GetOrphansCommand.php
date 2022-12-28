<?php

namespace Aurora\System\Console\Commands;

use Aurora\System\Console\Commands\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GetOrphansCommand extends BaseCommand
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
        $this->setName('get-orphans')
            ->setDescription('Collect orphan entries')
            ->addOption('remove', 'r',  InputOption::VALUE_NONE, 'Remove orphan entries from DB.')
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

            $this->logger->info('Checking ' . $model::query()->getQuery()->from . ' table.');

            $modelObject = new $model;
            $checkOrphan = $modelObject->getOrphanIds();
            switch($checkOrphan['status']){
                case 0:
                    $this->logger->info($checkOrphan['message']);
                    break;
                    case 1:
                        $aOrphansEntities[$model] = array_values($checkOrphan['orphansIds']);
                        if ($input->getOption('remove') && !empty($aOrphansEntities[$model])) {
                            $this->logger->error($checkOrphan['message']);
                            $bRemove = $helper->ask($input, $output, $question);

                            if ($bRemove) {
                                $modelObject::whereIn('id', $aOrphansEntities[$model])->delete();
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
        $this->rewriteFile($fdEntities, $this->jsonPretify($aOrphansEntities));
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

            $users = new \Aurora\Modules\Core\Models\User;
            $orphanUUIDs = array_values(array_diff($dirs, $users::pluck('UUID')->toArray()));
          
            $aOrphansEntities['PersonalFiles'] = $orphanUUIDs;
            $this->rewriteFile($fdEntities, $this->jsonPretify($aOrphansEntities));
                        
            if (!empty($orphanUUIDs)) {
                $this->logger->error("Personal files orphans were found: " . count($orphanUUIDs));

                if ($input->getOption('remove') && !empty($orphanUUIDs)) {
                    $bRemove = $helper->ask($input, $output, $question);
    
                    if ($bRemove) {
                        if (!is_dir($dirOrphanFiles)) {
                            mkdir($dirOrphanFiles); 
                        }
    
                        foreach ($orphanUUIDs as $orphanUUID) {
                            rename( $dirPersonalFiles."/".$orphanUUID, $dirOrphanFiles."/".$orphanUUID);
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $verbosityLevelMap = array(
            'notice' => OutputInterface::VERBOSITY_NORMAL,
            'info' => OutputInterface::VERBOSITY_NORMAL
        );
        $dirName = \Aurora\System\Api::DataPath() . "/get-orphans-logs";
        $entitiesFileName = $dirName . "/orphans_".date('Y-m-d_H-i-s').".json";
        
        $dirname = dirname($entitiesFileName);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }

        $fdEntities = fopen($entitiesFileName, 'a+') or die("Can't create migration-progress.txt file");

        $this->logger = new ConsoleLogger($output, $verbosityLevelMap);
        $this->checkOrphans($fdEntities, $input, $output);
        $this->checkFileOrphans($fdEntities, $input, $output);
        return Command::SUCCESS;
    }
}
