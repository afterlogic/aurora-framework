<?php

namespace Aurora\System\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class BaseCommand extends Command
{
    /**
     * Get all of the migration paths.
     *
     * @return array
     */
    protected function getMigrationPaths($input)
    {
        // Here, we will check to see if a path option has been defined. If it has we will
        // use the path relative to the root of the installation folder so our database
        // migrations may be run for any customized path from within the application.
        if ($input->getOption('path')) {
            return collect($input->getOption('path'))->map(function ($path) use ($input) {
                return !$this->usingRealPath($input)
                    ? \Aurora\Api::RootPath() . $path
                    : $path;
            })->all();
        }

        return array_merge(
            [$this->getSystemMigrationsPath()],
            $this->migrator->paths(), // @phpstan-ignore-line
            $this->getMigrationPath()
        );
    }

    /**
     * Determine if the given path(s) are pre-resolved "real" paths.
     *
     * @return bool
     */
    protected function usingRealPath($input)
    {
        return $input->hasOption('realpath') && $input->getOption('realpath');
    }

    /**
     * Get the path to the migration directory.
     *
     * @param null $sRequiredModule
     * @return string[]|string
     */
    protected function getMigrationPath($sRequiredModule = null)
    {
        if ($sRequiredModule) {
            if ($sRequiredModule === 'system') {
                $sPath = $this->getSystemMigrationsPath();
            } else {
                $sPath = \Aurora\Api::GetModuleManager()->GetModulePath($sRequiredModule) . $sRequiredModule . DIRECTORY_SEPARATOR . 'Migrations';
            }

            if (!file_exists($sPath)) {
                mkdir($sPath, 0755, true);
            }

            return $sPath;
        } else {
            $aModules = \Aurora\Api::GetModuleManager()->GetModulesPaths();
            return array_map(function ($sModule, $sPath) {
                return $sPath . $sModule . DIRECTORY_SEPARATOR . 'Migrations';
            }, array_keys($aModules), $aModules);
        }
    }

    public function getAllModels()
    {
        $modules = \Aurora\Api::GetModuleManager()->GetModulesPaths();

        array_walk($modules, function (&$modelPath, $module) {
            $modelPath = $modelPath . $module . DIRECTORY_SEPARATOR . 'Models';
        });

        $dirModels = [];
        foreach ($modules as $module => $moduleModelPath) {
            $finder = Finder::create();
            if (is_dir($moduleModelPath)) {
                $finder
                ->in($moduleModelPath)
                ->depth(0);
                $dirModels[$module] = array_keys(\iterator_to_array($finder));
            }
        }

        $formatedDirModels = [];
        foreach ($dirModels as $module => $moduleDirModels) {
            foreach ($moduleDirModels as $dirModel) {
                $modelName = pathinfo($dirModel, PATHINFO_FILENAME);
                $formatedDirModels[$module][$modelName] = dirname($dirModel) . DIRECTORY_SEPARATOR . $modelName;
            }
        }
        return $formatedDirModels;
    }

    /**
     * @return string
     */
    private function getSystemMigrationsPath()
    {
        return \Aurora\Api::RootPath() . DIRECTORY_SEPARATOR . 'Migrations';
    }
}
