<?php

namespace Aurora\System\Console\Commands\Migrations;

use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    /**
     * The repository instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationRepositoryInterface  $repository
     * @return void
     */
    public function __construct(MigrationRepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    protected function configure(): void
    {
        $this->setName('migrate:install')
            ->setDescription('Create the migration repository')
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->repository->setSource($input->getOption('database'));

        $this->repository->createRepository();

        $output->writeln('Migration table created successfully.');

        return Command::SUCCESS;
    }
}
