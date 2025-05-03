<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:fix-media-directories',
    description: 'Creates and fixes permissions for media upload directories',
)]
class FixMediaDirectoriesCommand extends Command
{
    private string $projectDir;
    
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This command creates and fixes permissions for media upload directories');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Media directories setup');
        
        $filesystem = new Filesystem();
        
        $directories = [
            $this->projectDir . '/var/uploads/temp',
            $this->projectDir . '/public/uploads/images',
            $this->projectDir . '/public/uploads/logos',
            $this->projectDir . '/public/uploads/promotions',
        ];
        
        foreach ($directories as $dir) {
            if (!$filesystem->exists($dir)) {
                $io->text("Creating directory: $dir");
                $filesystem->mkdir($dir, 0755);
                $io->success("Created $dir");
            } else {
                $io->note("Directory already exists: $dir");
                
                // Update permissions
                $io->text("Setting permissions on: $dir");
                chmod($dir, 0755);
            }
        }
        
        // Check if the directories are writable
        foreach ($directories as $dir) {
            if (is_writable($dir)) {
                $io->success("$dir is writable");
            } else {
                $io->error("$dir is NOT writable - please check permissions");
            }
        }
        
        $io->success('Media directories setup completed');
        
        return Command::SUCCESS;
    }
}
