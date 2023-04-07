<?php

namespace Aurora\System\Console\Commands;

use Aurora\System\Api;
use Aurora\System\Models\Hook;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ModelsCommand extends \Barryvdh\LaravelIdeHelper\Console\ModelsCommand
{
    public function __construct(Container $appContainer)
    {
        $appContainer['config']->set('ide-helper.model_hooks', [Hook::class]);
        $this->setLaravel($appContainer);

        parent::__construct($appContainer['filesystem']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->option('all-models')) {
            $aModules = Api::GetModuleManager()->GetModulesPaths();
            $aModulesModelsPaths = array_map(function ($sModule, $sPath) {
                return $sPath . $sModule . DIRECTORY_SEPARATOR . 'Models';
            }, array_keys($aModules), $aModules);
            App::make('config')->set('ide-helper.model_locations', [$aModulesModelsPaths]);
        }

        return parent::execute($input, $output);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = parent::getOptions();
        $options[] = ['all-models', 'A', InputOption::VALUE_NONE, 'Find and generate phpdocs for all models'];

        return $options;
    }
}
