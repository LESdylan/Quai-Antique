<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploader
{
    private $targetDirectory;
    private $slugger;

    public function __construct($targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        
        // Ensure the target directory exists
        if (!is_dir($this->targetDirectory)) {
            mkdir($this->targetDirectory, 0777, true);
        }
    }

    public function upload($file)
    {
        // Handle both uploaded files and direct paths
        if (is_string($file) && file_exists($file)) {
            return $this->copyFromPath($file);
        } elseif ($file instanceof UploadedFile) {
            return $this->uploadFile($file);
        } else {
            throw new FileException('Invalid file provided. Must be UploadedFile or valid file path.');
        }
    }

    private function uploadFile(UploadedFile $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->targetDirectory, $fileName);
        } catch (FileException $e) {
            throw $e;
        }

        return $fileName;
    }

    private function copyFromPath($filePath)
    {
        // Extract original name and extension
        $originalFilename = pathinfo($filePath, PATHINFO_FILENAME);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        // Create a unique filename
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$extension;
        
        // Copy the file to the target directory
        try {
            copy($filePath, $this->targetDirectory . '/' . $fileName);
        } catch (\Exception $e) {
            throw new FileException('Error copying file: ' . $e->getMessage());
        }
        
        return $fileName;
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}
