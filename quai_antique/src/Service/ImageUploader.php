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
    private $tempDirectory;

    public function __construct(string $targetDirectory, string $tempDirectory, SluggerInterface $slugger, LoggerInterface $logger = null)
    {
        $this->targetDirectory = $targetDirectory;
        $this->tempDirectory = $tempDirectory;
        $this->slugger = $slugger;
        $this->logger = $logger;
        
        // Ensure target directory exists
        $this->ensureTargetDirectoryExists();
    }

    /**
     * Upload a file from any location (uploaded file or filesystem path)
     */
    public function upload($file): string
    {
        try {
            if ($file instanceof UploadedFile) {
                // Handle standard upload
                return $this->handleUploadedFile($file);
            } elseif (is_string($file) && file_exists($file) && is_readable($file)) {
                // Handle file path
                return $this->handleFilePath($file);
            } else {
                throw new FileException('Invalid file source. Provide either an UploadedFile or a valid file path.');
            }
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

    /**
     * Handle an uploaded file
     */
    private function handleUploadedFile(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            throw new FileException('Failed to move uploaded file: ' . $e->getMessage());
        }

        return $fileName;
    }

    /**
     * Handle a file from filesystem path
     */
    private function handleFilePath(string $filePath): string
    {
        $originalFilename = pathinfo($filePath, PATHINFO_FILENAME);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $extension;

        try {
            // Copy the file to the target directory
            copy($filePath, $this->getTargetDirectory() . '/' . $fileName);
            
            // Check if copy was successful
            if (!file_exists($this->getTargetDirectory() . '/' . $fileName)) {
                throw new FileException('Failed to copy file from ' . $filePath);
            }
        } catch (\Exception $e) {
            throw new FileException('Failed to copy file: ' . $e->getMessage());
        }

        return $fileName;
    }

    /**
     * Create temporary file from base64 data
     */
    public function createTempFromBase64(string $base64Data): string
    {
        // Extract actual base64 string if it contains a data URI
        if (strpos($base64Data, ';base64,') !== false) {
            list(, $base64Data) = explode(';base64,', $base64Data);
        }
        
        $decodedData = base64_decode($base64Data);
        
        if ($decodedData === false) {
            throw new FileException('Invalid base64 data');
        }
        
        // Create temp directory if it doesn't exist
        if (!file_exists($this->tempDirectory)) {
            mkdir($this->tempDirectory, 0777, true);
        }
        
        $tempFile = tempnam($this->tempDirectory, 'img_');
        file_put_contents($tempFile, $decodedData);
        
        // Try to determine file extension
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tempFile);
        $extension = $this->getExtensionFromMimeType($mimeType);
        
        // Rename with proper extension
        $newTempFile = $tempFile . '.' . $extension;
        rename($tempFile, $newTempFile);
        
        return $newTempFile;
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];
        
        return $map[$mimeType] ?? 'jpg';
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
