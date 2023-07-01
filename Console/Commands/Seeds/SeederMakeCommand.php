<?php

namespace Aurora\System\Console\Commands\Seeds;

use Aurora\System\Console\Commands\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeederMakeCommand extends GeneratorCommand
{
    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    protected function configure(): void
    {
        $this->setName('make:seeder')
            ->setDescription('Create a new seeder class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the class');
    }

    /**
     * Create a new command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param \Illuminate\Support\Composer $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct($files);

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = parent::execute($input, $output);

        $this->composer->dumpAutoloads();

        return $result;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/../../stubs/seeder.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = \Aurora\Api::RootPath() . DIRECTORY_SEPARATOR . trim($stub, '/'))
            ? $customPath
            : __DIR__ . $stub;
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     * @return string
     */
    protected function getPath($name)
    {
        return \Aurora\Api::RootPath() . 'seeds' . DIRECTORY_SEPARATOR . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        return $name;
    }
}
