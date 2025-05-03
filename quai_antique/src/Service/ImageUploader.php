<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

class ImageUploader
{
    private $targetDirectory;
    private $slugger;
    private $logger;

    public function __construct(string $targetDirectory, SluggerInterface $slugger, LoggerInterface $logger = null)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->logger = $logger;
        
        // Ensure target directory exists
        $this->ensureTargetDirectoryExists();
    }

    public function upload($file): string
    {
        try {
            // Get original filename and create a safe version
            if ($file instanceof UploadedFile) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            } else {
                // Handle string file path
                $originalFilename = pathinfo($file, PATHINFO_FILENAME);
            }
            
            $safeFilename = $this->slugger->slug($originalFilename);
            $fileName = $safeFilename . '-' . uniqid() . '.' . ($file instanceof UploadedFile ? $file->guessExtension() : pathinfo($file, PATHINFO_EXTENSION));

            // Move the file to the target directory
            if ($file instanceof UploadedFile) {
                $file->move($this->getTargetDirectory(), $fileName);
            } else {
                // Copy the file from the given path
                if (!copy($file, $this->getTargetDirectory() . '/' . $fileName)) {
                    throw new FileException('Failed to copy file from path');
                }
            }
            
            return $fileName;
        } catch (FileException $e) {
            if ($this->logger) {
                $this->logger->error('Failed to upload file', [
                    'error' => $e->getMessage(),
                    'file' => $file instanceof UploadedFile ? $file->getClientOriginalName() : $file
                ]);
            }
            throw $e;
        }
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
    
    /**
     * Ensure the target directory exists and is writable
     */
    private function ensureTargetDirectoryExists(): void
    {
        if (!file_exists($this->targetDirectory)) {
            if (!mkdir($this->targetDirectory, 0777, true) && !is_dir($this->targetDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->targetDirectory));
            }
            
            // Set proper permissions
            chmod($this->targetDirectory, 0777);
            
            if ($this->logger) {
                $this->logger->info('Created upload directory: ' . $this->targetDirectory);
            }
        } elseif (!is_writable($this->targetDirectory)) {
            // Try to make directory writable
            chmod($this->targetDirectory, 0777);
            
            if (!is_writable($this->targetDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" is not writable', $this->targetDirectory));
            }
        }
    }
    
    /**
     * Delete an image file
     */
    public function remove(string $filename): bool
    {
        $filePath = $this->targetDirectory . '/' . $filename;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }
}
