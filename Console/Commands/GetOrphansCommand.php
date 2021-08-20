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

    protected function checkOrphans()
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

            $this->logger->info('checking ' . $model::query()->getQuery()->from);

            $modelObject = new $model;
            $checkOrphan = $modelObject->getOrphanIds();
                switch($checkOrphan['status']){
                    case 0:
                        $this->logger->info($checkOrphan['message']);
                        break;
                    case 1:
                        $this->logger->error($checkOrphan['message']);
                        break;
                    default:
                        $this->logger->info($checkOrphan['message']);
                        break;
                }
                echo PHP_EOL;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $verbosityLevelMap = array(
            'notice' => OutputInterface::VERBOSITY_NORMAL,
            'info' => OutputInterface::VERBOSITY_NORMAL
        );

        $this->logger = new ConsoleLogger($output, $verbosityLevelMap);
        $this->checkOrphans();
        return Command::SUCCESS;
    }
}
