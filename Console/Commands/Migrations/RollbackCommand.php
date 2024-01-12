<?php

namespace Aurora\System\Console\Commands\Migrations;

use Aurora\System\Console\Commands\BaseCommand;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RollbackCommand extends BaseCommand
{
    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    protected function configure(): void
    {
        $this->setName('migrate:rollback')
            ->setDescription('Rollback the last database migration')
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The path(s) to the migrations files to be executed')
            ->addOption('realpath', null, InputOption::VALUE_NONE, 'Indicate any provided migration file paths are pre-resolved absolute paths')
            ->addOption('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run')
            ->addOption('step', null, InputOption::VALUE_OPTIONAL, 'The number of migrations to be reverted');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $defaultAnswer = $input->getOption('no-interaction');
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you really wish to run this command? (Y/N)', $defaultAnswer);
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $this->migrator->usingConnection($input->getOption('database'), function () use ($input, $output) {
            $this->migrator->setOutput($output)->rollback(
                $this->getMigrationPaths($input),
                [
                    'pretend' => $input->getOption('pretend'),
                    'step' => (int) $input->getOption('step'),
                ]
            );
        });

        return Command::SUCCESS;
    }
}
