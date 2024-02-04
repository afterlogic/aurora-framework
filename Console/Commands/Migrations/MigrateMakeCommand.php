<?php

namespace Aurora\System\Console\Commands\Migrations;

use Aurora\System\Console\Commands\BaseCommand;
use Illuminate\Database\Console\Migrations\TableGuesser;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateMakeCommand extends BaseCommand
{
    /**
     * The migration creator instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * @param \Illuminate\Database\Migrations\MigrationCreator $creator
     * @param \Illuminate\Support\Composer $composer
     * @return void
     */
    public function __construct(MigrationCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
    }

    protected function configure(): void
    {
        $this->setName('make:migration')
            ->setDescription('Create the migration repository')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration')
            ->addArgument('module', InputArgument::REQUIRED, 'The module for the migration')
            ->addOption('create', null, InputOption::VALUE_OPTIONAL, 'The table to be created')
            ->addOption('table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The location where the migration file should be created')
            ->addOption('realpath', null, InputOption::VALUE_OPTIONAL, 'Indicate any provided migration file paths are pre-resolved absolute paths')
            ->addOption('fullpath', null, InputOption::VALUE_OPTIONAL, 'Output the full path of the migration');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $name = Str::snake(trim($input->getArgument('name')));

        $table = $input->getOption('table');

        $create = $input->getOption('create') ?: false;

        // If no table was given as an option but a create option is given then we
        // will use the "create" option as the table name. This allows the devs
        // to pass a table name into this option as a short-cut for creating.
        if (!$table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        // Next, we will attempt to guess the table name if this the migration has
        // "create" in the name. This will allow us to provide a convenient way
        // of creating migrations that create new tables for the application.
        if (!$table) {
            [$table, $create] = TableGuesser::guess($name);
        }

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name, $table, $create, $input, $output);

        $this->composer->dumpAutoloads();

        return Command::SUCCESS;
    }

    /**
     * Write the migration file to disk.
     *
     * @param string $name
     * @param string $table
     * @param bool $create
     * @return void
     */
    protected function writeMigration($name, $table, $create, $input, $output)
    {
        $file = $this->creator->create(
            $name,
            $this->getMigrationPath($input->getArgument('module')),
            $table,
            $create
        );

        if (!$input->getOption('fullpath')) {
            $file = pathinfo($file, PATHINFO_FILENAME);
        }

        $output->writeln("<info>Created Migration:</info> {$file}");
    }
}
