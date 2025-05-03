<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait UpdateTimestampsTrait
{
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $now = new \DateTime();
        
        // For fields named 'updatedAt' (camelCase)
        if (property_exists($this, 'updatedAt')) {
            $this->updatedAt = $now;
        }
        
        // For fields named 'updateDate' (camelCase)
        if (property_exists($this, 'updateDate')) {
            $this->updateDate = $now;
        }
        
        // For fields named 'updated_at' (snake_case)
        if (property_exists($this, 'updated_at')) {
            $this->{'updated_at'} = $now;
        }
        
        // For fields named 'update_date' (snake_case)
        if (property_exists($this, 'update_date')) {
            $this->{'update_date'} = $now;
        }
        
        // Handle case where createdAt is null on an update operation
        if (property_exists($this, 'createdAt') && $this->createdAt === null) {
            $this->createdAt = $now;
        }
        
        // Handle case where create_date is null on an update operation
        if (property_exists($this, 'create_date') && $this->{'create_date'} === null) {
            $this->{'create_date'} = $now;
        }
    }
}
