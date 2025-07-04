<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:fix-config',
    description: 'Fixes configuration settings in the database',
)]
class FixConfigCommand extends Command
{
    private $projectDir;
    
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command helps fix configuration settings in the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();
        
        $io->title('Configuration Fixer');
        $io->section('Clearing cache directories');
        
        // Clear cache directories
        $cacheDirs = [
            $this->projectDir . '/var/cache/dev',
            $this->projectDir . '/var/cache/prod'
        ];
        
        foreach ($cacheDirs as $cacheDir) {
            if ($fs->exists($cacheDir)) {
                $io->text("Removing: $cacheDir");
                $fs->remove($cacheDir);
                $io->success("Removed $cacheDir");
            } else {
                $io->note("Directory $cacheDir doesn't exist");
            }
        }
        
        $io->section('Checking Doctrine Migrations configuration');
        
        // Ensure doctrine migrations config exists
        $migrationsConfigFile = $this->projectDir . '/config/packages/doctrine_migrations.yaml';
        if (!$fs->exists($migrationsConfigFile)) {
            $io->text("Creating migrations configuration file");
            
            $config = <<<YAML
doctrine_migrations:
    migrations_paths:
        'DoctrineMigrations': '%kernel.project_dir%/migrations'
    enable_profiler: '%kernel.debug%'
YAML;
            
            $fs->dumpFile($migrationsConfigFile, $config);
            $io->success("Created migrations configuration file");
        } else {
            $io->note("Migrations configuration file already exists");
        }
        
        $io->success('Configuration fixed and cache cleared');
        
        return Command::SUCCESS;
    }
}
