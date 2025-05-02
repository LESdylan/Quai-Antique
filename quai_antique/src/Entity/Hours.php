<?php

namespace App\Entity;

use App\Repository\HoursRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HoursRepository::class)]
class Hours
{
    public const DAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
        7 => 'Dimanche',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $dayOfWeek = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lunchOpeningTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lunchClosingTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dinnerOpeningTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dinnerClosingTime = null;

    #[ORM\Column]
    private ?bool $isClosed = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

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

    public function isIsClosed(): ?bool
    {
        return $this->isClosed;
    }

    public function setIsClosed(bool $isClosed): static
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    public function getDayName(): string
    {
        return self::DAYS[$this->dayOfWeek] ?? 'Unknown';
    }
}
