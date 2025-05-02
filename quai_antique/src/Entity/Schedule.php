<?php

namespace App\Entity;

use App\Repository\ScheduleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ScheduleRepository::class)
 */
class Schedule
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="integer")
     */
    private int $dayOfWeek;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $lunchOpeningTime = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $lunchClosingTime = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $dinnerOpeningTime = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $dinnerClosingTime = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isClosed;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): self
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getLunchOpeningTime(): ?\DateTimeInterface
    {
        return $this->lunchOpeningTime;
    }

    public function setLunchOpeningTime(?\DateTimeInterface $lunchOpeningTime): self
    {
        $this->lunchOpeningTime = $lunchOpeningTime;

        return $this;
    }

    public function getLunchClosingTime(): ?\DateTimeInterface
    {
        return $this->lunchClosingTime;
    }

    public function setLunchClosingTime(?\DateTimeInterface $lunchClosingTime): self
    {
        $this->lunchClosingTime = $lunchClosingTime;

        return $this;
    }

    public function getDinnerOpeningTime(): ?\DateTimeInterface
    {
        return $this->dinnerOpeningTime;
    }

    public function setDinnerOpeningTime(?\DateTimeInterface $dinnerOpeningTime): self
    {
        $this->dinnerOpeningTime = $dinnerOpeningTime;

        return $this;
    }

    public function getDinnerClosingTime(): ?\DateTimeInterface
    {
        return $this->dinnerClosingTime;
    }

    public function setDinnerClosingTime(?\DateTimeInterface $dinnerClosingTime): self
    {
        $this->dinnerClosingTime = $dinnerClosingTime;

        return $this;
    }

    public function getIsClosed(): bool
    {
        return $this->isClosed;
    }

    public function setIsClosed(bool $isClosed): self
    {
        $this->isClosed = $isClosed;

        return $this;
    }
}
