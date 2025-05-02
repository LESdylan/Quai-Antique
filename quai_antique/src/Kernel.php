<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Dotenv\Dotenv;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
    
    public function boot(): void
    {
        // Ensure all environment variables are loaded
        if (file_exists(dirname(__DIR__).'/.env')) {
            (new Dotenv())->loadEnv(dirname(__DIR__).'/.env');
        }
        
        // Load custom MySQL configuration if needed
        $this->checkDatabaseConnectivity();
        
        parent::boot();
    }
    
    private function checkDatabaseConnectivity(): void
    {
        // Load the custom PHP MySQL config if it exists
        $customConfig = dirname(__DIR__).'/config/php-mysql-config.ini';
        if (file_exists($customConfig)) {
            // Load configuration silently
            @ini_set('auto_prepend_file', $customConfig);
        }
    }
}
