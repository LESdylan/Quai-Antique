<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(
    name: 'app:setup:assets',
    description: 'Sets up required asset directories and downloads placeholder images',
)]
class SetupAssetsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Setting up assets for the Quai Antique website');

        $filesystem = new Filesystem();
        $projectDir = dirname(__DIR__, 2); // Go up two levels to project root
        
        // Create the images directory if it doesn't exist
        $imagesDir = $projectDir . '/public/images';
        if (!$filesystem->exists($imagesDir)) {
            $filesystem->mkdir($imagesDir, 0777);
            $io->success("Created images directory: $imagesDir");
        } else {
            $io->info("Images directory already exists: $imagesDir");
        }
        
        // Placeholder images to download
        $placeholders = [
            'hero-bg.jpg' => 'https://source.unsplash.com/1600x900/?restaurant,dinner',
            'chef.jpg' => 'https://source.unsplash.com/800x600/?chef,cooking',
            'dish1.jpg' => 'https://source.unsplash.com/800x600/?food,appetizer',
            'dish2.jpg' => 'https://source.unsplash.com/800x600/?food,main,course',
            'dish3.jpg' => 'https://source.unsplash.com/800x600/?food,dessert',
            'reservation-bg.jpg' => 'https://source.unsplash.com/1600x900/?restaurant,table',
            'gallery-header.jpg' => 'https://source.unsplash.com/1600x900/?food,cooking',
            'contact-header.jpg' => 'https://source.unsplash.com/1600x900/?restaurant,contact',
            'favicon.ico' => 'https://raw.githubusercontent.com/symfony/symfony-docs/master/images/favicon.ico'
        ];
        
        // Download placeholder images
        $httpClient = HttpClient::create();
        $io->section('Downloading placeholder images');
        
        foreach ($placeholders as $filename => $url) {
            $imagePath = $imagesDir . '/' . $filename;
            
            if (!$filesystem->exists($imagePath)) {
                $io->text("Downloading $filename from $url");
                
                try {
                    $response = $httpClient->request('GET', $url);
                    $content = $response->getContent();
                    $filesystem->dumpFile($imagePath, $content);
                    $io->text("✓ Downloaded $filename");
                } catch (\Exception $e) {
                    $io->error("Failed to download $filename: " . $e->getMessage());
                }
            } else {
                $io->text("Image $filename already exists, skipping download");
            }
        }
        
        // Create additional asset directories if needed
        $additionalDirs = [
            $projectDir . '/public/uploads',
            $projectDir . '/public/uploads/images',
            $projectDir . '/public/css',
            $projectDir . '/public/js',
        ];
        
        foreach ($additionalDirs as $dir) {
            if (!$filesystem->exists($dir)) {
                $filesystem->mkdir($dir, 0777);
                $io->text("Created directory: $dir");
            }
        }
        
        $io->success('Asset setup complete!');
        $io->note('If you need to add specific images, place them in the public/images directory.');
        
        return Command::SUCCESS;
    }
}
