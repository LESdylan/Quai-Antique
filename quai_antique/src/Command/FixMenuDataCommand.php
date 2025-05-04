<?php

namespace App\Command;

use App\Entity\Menu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-menu-data',
    description: 'Fixes NULL values in meal_type column of Menu entity',
)]
class FixMenuDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Repairing Menu data...');

        // Step 1: Direct database query to fix existing NULL values
        $connection = $this->entityManager->getConnection();
        $sql = "UPDATE menu SET meal_type = 'main' WHERE meal_type IS NULL";
        $rowsUpdated = $connection->executeStatement($sql);
        $io->success("Fixed $rowsUpdated records with NULL meal_type using direct SQL");

        // Step 2: Load all menus and ensure they have proper meal_type
        $menus = $this->entityManager->getRepository(Menu::class)->findAll();
        $fixedEntities = 0;

        foreach ($menus as $menu) {
            if (!$menu->getMealType()) {
                $menu->setMealType('main');
                $fixedEntities++;
            }
        }

        if ($fixedEntities > 0) {
            $this->entityManager->flush();
            $io->success("Fixed $fixedEntities Menu entities with missing meal_type using ORM");
        } else {
            $io->info("No Menu entities needed fixing through ORM");
        }

        // Step 3: Update database schema to add default value if needed
        $io->section("Ensure database schema has default value for meal_type");
        $io->text("To update the database schema with a default value, run:");
        $io->text("php bin/console doctrine:migrations:diff");
        $io->text("php bin/console doctrine:migrations:migrate");

        $io->success("Menu data repair completed successfully!");
        return Command::SUCCESS;
    }
}
