<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:create-hours-table',
    description: 'Creates the hours table in the database',
)]
class CreateHoursTableCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creating Hours Table');

        $connection = $this->entityManager->getConnection();
        $dbName = $connection->getDatabase();
        
        try {
            // Check if table already exists
            $tableExists = $connection->executeQuery("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?
            ", [$dbName, 'hours'])->fetchOne();
            
            if ($tableExists) {
                $io->info('Table "hours" already exists. Skipping creation.');
                return Command::SUCCESS;
            }
            
            // Create the hours table
            $sql = "CREATE TABLE `hours` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                day_of_week INT NOT NULL,
                lunch_opening_time TIME DEFAULT NULL,
                lunch_closing_time TIME DEFAULT NULL,
                dinner_opening_time TIME DEFAULT NULL,
                dinner_closing_time TIME DEFAULT NULL,
                is_closed TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $connection->executeStatement($sql);
            $io->success('Successfully created the "hours" table');
            
            // Insert default opening hours
            $io->section('Inserting default opening hours');
            
            // Days 1-7 (Monday to Sunday)
            for ($day = 1; $day <= 7; $day++) {
                // Monday is closed (day 1)
                $isClosed = ($day === 1) ? 1 : 0;
                
                $sql = "INSERT INTO `hours` 
                    (day_of_week, lunch_opening_time, lunch_closing_time, dinner_opening_time, dinner_closing_time, is_closed)
                    VALUES 
                    (?, ?, ?, ?, ?, ?)";
                
                $connection->executeStatement($sql, [
                    $day,
                    '12:00:00',
                    '14:00:00', 
                    '19:00:00', 
                    '22:00:00',
                    $isClosed
                ]);
                
                $dayName = match($day) {
                    1 => 'Monday',
                    2 => 'Tuesday',
                    3 => 'Wednesday',
                    4 => 'Thursday',
                    5 => 'Friday',
                    6 => 'Saturday',
                    7 => 'Sunday',
                    default => "Unknown day ($day)"
                };
                
                $io->text("Added hours for $dayName");
            }
            
            $io->success('Default opening hours added successfully');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating hours table: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
