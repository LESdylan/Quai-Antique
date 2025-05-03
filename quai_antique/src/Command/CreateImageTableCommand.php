<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:create-image-table',
    description: 'Creates the image table in the database',
)]
class CreateImageTableCommand extends Command
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
        $io->title('Creating Image Table');

        $connection = $this->entityManager->getConnection();
        $dbName = $connection->getDatabase();
        
        try {
            // Check if table already exists
            $tableExists = $connection->executeQuery("
                SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?
            ", [$dbName, 'image'])->fetchOne();
            
            if ($tableExists) {
                $io->info('Table "image" already exists. Skipping creation.');
                return Command::SUCCESS;
            }
            
            // Create the image table
            $sql = "CREATE TABLE `image` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255) DEFAULT NULL,
                mime_type VARCHAR(255) DEFAULT NULL,
                alt VARCHAR(255) NOT NULL,
                category VARCHAR(64) DEFAULT NULL,
                dish_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                CONSTRAINT fk_image_dish FOREIGN KEY (dish_id) REFERENCES dish(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $connection->executeStatement($sql);
            $io->success('Successfully created the "image" table');
            
            // Ensure uploads directory exists
            $uploadsDir = dirname(__DIR__, 2) . '/public/uploads/images';
            if (!is_dir($uploadsDir)) {
                if (mkdir($uploadsDir, 0777, true)) {
                    $io->success('Created image uploads directory at: ' . $uploadsDir);
                } else {
                    $io->warning('Could not create uploads directory. Please create it manually: ' . $uploadsDir);
                }
            } else {
                $io->info('Uploads directory already exists at: ' . $uploadsDir);
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error creating image table: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
