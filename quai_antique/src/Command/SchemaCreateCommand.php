<?php

namespace App\Command;

use App\Service\SchemaToolHelper;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:schema:create',
    description: 'Creates the database schema directly using SQL',
)]
class SchemaCreateCommand extends Command
{
    private Connection $connection;
    private SchemaToolHelper $schemaToolHelper;

    public function __construct(Connection $connection, SchemaToolHelper $schemaToolHelper)
    {
        parent::__construct();
        $this->connection = $connection;
        $this->schemaToolHelper = $schemaToolHelper;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creating database schema');

        try {
            $this->schemaToolHelper->createTables($this->connection, $io);
            $io->success('Database schema created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating database schema: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
