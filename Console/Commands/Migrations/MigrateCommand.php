<?php

namespace Aurora\System\Console\Commands\Migrations;

use Aurora\System\Console\Commands\BaseCommand;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MigrateCommand extends BaseCommand
{
    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration command instance.
     *
     * @param \Illuminate\Database\Migrations\Migrator $migrator
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    protected function configure(): void
    {
        $this->setName('migrate')
            ->setDescription('Run the database migrations')
            ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'Force the operation to run when in production')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The path(s) to the migrations files to be executed')
            ->addOption('realpath', null, InputOption::VALUE_OPTIONAL, 'Indicate any provided migration file paths are pre-resolved absolute paths')
            ->addOption('pretend', null, InputOption::VALUE_OPTIONAL, 'Dump the SQL queries that would be run')
            ->addOption('seed', null, InputOption::VALUE_OPTIONAL, 'Indicates if the seed task should be re-run')
            ->addOption('step', null, InputOption::VALUE_OPTIONAL, 'Force the migrations to be run so they can be rolled back individually')
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $defaultAnswer = $input->getOption('no-interaction');
        if (!$input->getOption('force')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Do you really wish to run this command? (Y/N)', $defaultAnswer);
            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $this->migrator->usingConnection($input->getOption('database'), function () use ($input, $output) {
            $this->prepareDatabase($input, $output);

            // Next, we will check to see if a path option has been defined. If it has
            // we will use the path relative to the root of this installation folder
            // so that migrations may be run for any path within the applications.

            $this->migrator->setOutput($output)
                ->run($this->getMigrationPaths($input), [
                    'pretend' => $input->getOption('pretend'),
                    'step' => $input->getOption('step'),
                ]);

            // Finally, if the "seed" option has been given, we will re-run the database
            // seed task to re-populate the database, which is convenient when adding
            // a migration and a seed at the same time, as it is only this command.
            if ($input->getOption('seed') && ! $input->getOption('pretend')) {
                $seedInput = new ArrayInput([
                    '--force'  => true,
                ]);
                $this->getApplication()->find('db:seed')->run($seedInput, $output);
            }
        });

        return Command::SUCCESS;
    }

    /**
     * Prepare the migration database for running.
     *
     * @return void
     */
    protected function prepareDatabase($input, $output)
    {
        if (!$this->migrator->repositoryExists()) {
            $greetInput = new ArrayInput([
                '--database' => $input->getOption('database'),
            ]);
            $this->getApplication()->find('migrate:install')->run($greetInput, $output);
        }
    }
}
