<?php

namespace App\Entity;

use App\Repository\ScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $dayName = null;
    
    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $dayNumber = null;
    
    #[ORM\Column(name: "day_of_week", type: Types::STRING, length: 20)]
    private ?string $dayOfWeek = null;

    #[ORM\Column]
    private ?bool $isClosed = null;

    #[ORM\Column(length: 20)]
    private ?string $mealType = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $openingTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $closingTime = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lunchOpeningTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lunchClosingTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dinnerOpeningTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dinnerClosingTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayName(): ?string
    {
        return $this->dayName;
    }

    public function setDayName(string $dayName): static
    {
        $this->dayName = $dayName;
        // Auto-set dayOfWeek when dayName is set
        $this->setDayOfWeek($dayName);
        
        return $this;
    }
    
    public function getDayNumber(): ?int
    {
        return $this->dayNumber;
    }
    
    public function setDayNumber(int $dayNumber): static
    {
        $this->dayNumber = $dayNumber;
        
        return $this;
    }
    
    public function getDayOfWeek(): ?string
    {
        return $this->dayOfWeek;
    }
    
    public function setDayOfWeek(string $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }

    public function getMealType(): ?string
    {
        return $this->mealType;
    }

    public function setMealType(string $mealType): static
    {
        $this->mealType = $mealType;

        return $this;
    }

    public function getOpeningTime(): ?\DateTimeInterface
    {
        return $this->openingTime;
    }

    public function setOpeningTime(\DateTimeInterface $openingTime): static
    {
        $this->openingTime = $openingTime;

        return $this;
    }

    public function getClosingTime(): ?\DateTimeInterface
    {
        return $this->closingTime;
    }

    public function setClosingTime(\DateTimeInterface $closingTime): static
    {
        $this->closingTime = $closingTime;

        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isIsClosed(): ?bool
    {
        return $this->isClosed;
    }

    public function setIsClosed(bool $isClosed): static
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    public function getLunchOpeningTime(): ?\DateTimeInterface
    {
        return $this->lunchOpeningTime;
    }

    public function setLunchOpeningTime(?\DateTimeInterface $lunchOpeningTime): static
    {
        $this->lunchOpeningTime = $lunchOpeningTime;

        return $this;
    }

    public function getLunchClosingTime(): ?\DateTimeInterface
    {
        return $this->lunchClosingTime;
    }

    public function setLunchClosingTime(?\DateTimeInterface $lunchClosingTime): static
    {
        $this->lunchClosingTime = $lunchClosingTime;

        return $this;
    }

    public function getDinnerOpeningTime(): ?\DateTimeInterface
    {
        return $this->dinnerOpeningTime;
    }

    public function setDinnerOpeningTime(?\DateTimeInterface $dinnerOpeningTime): static
    {
        $this->dinnerOpeningTime = $dinnerOpeningTime;

        return $this;
    }

    public function getDinnerClosingTime(): ?\DateTimeInterface
    {
        return $this->dinnerClosingTime;
    }

    public function setDinnerClosingTime(?\DateTimeInterface $dinnerClosingTime): static
    {
        $this->dinnerClosingTime = $dinnerClosingTime;

        return $this;
    }
    
    /**
     * Get day name based on day of week number
     */
    public function getDayNameFromNumber(): string
    {
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche'
        ];
        
        return $days[$this->dayOfWeek] ?? 'Inconnu';
    }
}
