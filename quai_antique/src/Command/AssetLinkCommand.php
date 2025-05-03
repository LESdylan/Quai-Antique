<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:asset:link',
    description: 'Creates symlinks for assets in the public directory',
)]
class AssetLinkCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();
        $projectDir = dirname(__DIR__, 2); // Go up two levels to project root
        
        $links = [
            // Link assets/gallery to public/images/gallery
            $projectDir . '/assets/gallery' => $projectDir . '/public/images/gallery',
        ];
        
        foreach ($links as $source => $target) {
            // Create source directory if it doesn't exist
            if (!$filesystem->exists($source)) {
                $filesystem->mkdir($source);
                $io->note(sprintf('Created directory: %s', $source));
            }
            
            // Create target directory if it doesn't exist
            $targetDir = dirname($target);
            if (!$filesystem->exists($targetDir)) {
                $filesystem->mkdir($targetDir);
                $io->note(sprintf('Created directory: %s', $targetDir));
            }
            
            // Create symlink if it doesn't exist
            if (!$filesystem->exists($target)) {
                // We need a relative link for better portability
                $relativeSource = rtrim($filesystem->makePathRelative($source, $targetDir), '/');
                
                // Remove target if it exists but is not a symlink
                if ($filesystem->exists($target) && !is_link($target)) {
                    $filesystem->remove($target);
                }
                
                // Create symlink
                $filesystem->symlink($relativeSource, $target);
                $io->success(sprintf('Created symlink: %s -> %s', $target, $relativeSource));
            } else {
                $io->info(sprintf('Symlink already exists: %s', $target));
            }
        }
        
        $io->success('All asset links created successfully.');
        
        return Command::SUCCESS;
    }
}
