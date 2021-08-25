<?php

namespace Aurora\System\Console\Commands;

use Aurora\System\Console\Commands\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class GetOrphansCommand extends BaseCommand
{
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
            ->setDescription('Get orphans');
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
            $sOutput .= PHP_EOL . "\t\t" . implode(',', $value);
            $sOutput .= PHP_EOL . "\t]";
        }
        $sOutput .= PHP_EOL . '}';
        $sOutput = str_replace('\\', '\\\\', $sOutput);

        return $sOutput;
    }

    protected function checkOrphans($fdEntities)
    {
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

            $this->logger->info('checking ' . $model::query()->getQuery()->from);

            $modelObject = new $model;
            $checkOrphan = $modelObject->getOrphanIds();
            switch($checkOrphan['status']){
                case 0:
                    $this->logger->info($checkOrphan['message']);
                    break;
                    case 1:
                        $aOrphansEntities[$model] = array_values($checkOrphan['orphansIds']);
                        $this->logger->error($checkOrphan['message']);
                        break;
                    default:
                        $this->logger->info($checkOrphan['message']);
                        break;
                }
                echo PHP_EOL;
        }
        $this->rewriteFile($fdEntities, $this->jsonPretify($aOrphansEntities));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $verbosityLevelMap = array(
            'notice' => OutputInterface::VERBOSITY_NORMAL,
            'info' => OutputInterface::VERBOSITY_NORMAL
        );
        $dirName = \Aurora\System\Api::DataPath() . "/get-orphans-logs";
        $entitiesFileName = $dirName . "/entries-".time().".txt";
        
        $dirname = dirname($entitiesFileName);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0755, true);
        }

        $fdEntities = fopen($entitiesFileName, 'a+') or die("Can't create migration-progress.txt file");

        $this->logger = new ConsoleLogger($output, $verbosityLevelMap);
        $this->checkOrphans($fdEntities);
        return Command::SUCCESS;
    }
}
