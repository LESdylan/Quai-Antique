<?php

namespace App\Command;

use App\Entity\Image;
use App\Entity\Category;
use App\Entity\Dish;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:import:sample-images',
    description: 'Imports sample images for the restaurant website',
)]
class ImportSampleImagesCommand extends Command
{
    private $entityManager;
    private $projectDir;

    public function __construct(EntityManagerInterface $entityManager, string $projectDir)
    {
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importing Sample Images for Quai Antique Restaurant');

        // 1. Make sure the uploads directory exists
        $uploadsDir = $this->projectDir . '/public/uploads/images';
        $sampleImagesDir = $this->projectDir . '/sample-images';
        
        $filesystem = new Filesystem();
        if (!$filesystem->exists($uploadsDir)) {
            $io->note("Creating uploads directory: $uploadsDir");
            $filesystem->mkdir($uploadsDir);
        }
        
        // Create sample-images directory if it doesn't exist
        if (!$filesystem->exists($sampleImagesDir)) {
            $io->note("Creating sample images directory: $sampleImagesDir");
            $filesystem->mkdir($sampleImagesDir);
            $io->warning('Sample images directory created, but no sample images exist yet.');
            $io->text('Please add some sample JPG images to: ' . $sampleImagesDir);
            $io->text('Then run this command again.');
            return Command::SUCCESS;
        }
        
        // Find existing images in the sample directory
        $finder = new Finder();
        $finder->files()->in($sampleImagesDir)->name(['*.jpg', '*.jpeg', '*.png']);
        
        if (!$finder->hasResults()) {
            $io->warning('No sample images found in: ' . $sampleImagesDir);
            $io->text('Please add some sample JPG/PNG images then run this command again.');
            return Command::SUCCESS;
        }

        // 2. Copy sample images to uploads directory
        $io->section('Copying sample images to uploads directory');
        
        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        $dishes = $this->entityManager->getRepository(Dish::class)->findAll();
        
        $imageCategories = ['menu', 'dish', 'gallery', 'interior', 'chef'];
        $imageCount = 0;
        $dishIndex = 0;
        
        foreach ($finder as $file) {
            $sourceFilename = $file->getFilename();
            $newFilename = uniqid() . '.' . $file->getExtension();
            
            try {
                // Copy the file
                $filesystem->copy(
                    $file->getRealPath(), 
                    $uploadsDir . '/' . $newFilename,
                    true // Override if exists
                );
                
                // 3. Create database entry for each image
                $image = new Image();
                $image->setFilename($newFilename);
                $image->setOriginalFilename($sourceFilename);
                $image->setAlt('Sample image: ' . $sourceFilename);
                $image->setIsActive(true);
                
                // Assign to categories in round-robin fashion
                $category = $imageCategories[$imageCount % count($imageCategories)];
                $image->setCategory($category);
                
                // For dish images, assign to a dish if available
                if ($category === 'dish' && !empty($dishes)) {
                    if ($dishIndex >= count($dishes)) {
                        $dishIndex = 0;
                    }
                    $image->setDish($dishes[$dishIndex]);
                    $dishIndex++;
                }
                
                $this->entityManager->persist($image);
                $io->text("Added image: $sourceFilename as $newFilename (category: $category)");
                
                $imageCount++;
            } catch (\Exception $e) {
                $io->error("Could not process image {$sourceFilename}: " . $e->getMessage());
            }
        }
        
        if ($imageCount > 0) {
            $this->entityManager->flush();
            $io->success("Successfully imported $imageCount sample images");
            
            // Add instructions for viewing the images
            $io->section('Next steps:');
            $io->text('1. Visit your website to see the imported images');
            $io->text('2. Go to the admin panel to manage these images');
        } else {
            $io->warning('No images were imported');
        }

        return Command::SUCCESS;
    }
}
