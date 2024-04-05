<?php

namespace Aurora\System\Console\Commands\Seeds;

use Symfony\Component\Console\Command\Command;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class SeedCommand extends Command
{
    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    protected function configure(): void
    {
        $this->setName('db:seed')
            ->setDescription('Seed the database with records')
            ->addOption('class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder')
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed')
            ->addOption('force', null, InputOption::VALUE_OPTIONAL, 'Force the operation to run when in production');
    }

    /**
     * Create a new database seed command instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
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

        $previousConnection = $this->resolver->getDefaultConnection();

        $this->resolver->setDefaultConnection($this->getDatabase($input));

        Model::unguarded(function () use ($input) {
            $this->getSeeder($input)->__invoke();
        });

        if ($previousConnection) {
            $this->resolver->setDefaultConnection($previousConnection);
        }

        $output->writeln('Database seeding completed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder(InputInterface $input)
    {
        $class = $input->getOption('class') ?? \DatabaseSeeder::class;

        return new $class();
    }

    /**
     * Get the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase(InputInterface $input)
    {
        $database = $input->getOption('database');

        return $database ?: $this->resolver->getDefaultConnection();
    }
}
