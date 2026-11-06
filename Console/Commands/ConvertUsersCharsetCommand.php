<?php

namespace Aurora\System\Console\Commands;

use Aurora\Modules\Core\Classes\UsersTableConverter;
use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertUsersCharsetCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('users:convert-charset')
            ->setDescription('Convert the core_users table to utf8mb4 if the migration had to skip it');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $problem = (new UsersTableConverter(Capsule::connection()))->convert();

        if ($problem !== null) {
            $output->writeln('<error>' . $problem . '</error>');

            return self::FAILURE;
        }

        $output->writeln('<info>The users table is in utf8mb4.</info>');

        return self::SUCCESS;
    }
}
