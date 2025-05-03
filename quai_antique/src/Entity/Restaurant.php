<?php

namespace App\Entity;

use App\Repository\RestaurantRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Restaurant
{
    use UpdateTimestampsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagramUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tripadvisorUrl = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $averagePriceLunch = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $averagePriceDinner = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoFilename = null;

    #[ORM\Column(nullable: true)]
    private ?bool $displayOpeningHours = true;

    #[ORM\Column]
    private ?int $maxGuests = 50;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $update_date = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ["default" => 50])]
    private ?int $maxCapacity = 50;

    #[ORM\Column(type: 'integer', nullable: true, options: ["default" => 6])]
    private ?int $maxTablesSmall = 6;

    #[ORM\Column(type: 'integer', nullable: true, options: ["default" => 8])]
    private ?int $maxTablesMedium = 8;

    #[ORM\Column(type: 'integer', nullable: true, options: ["default" => 4])]
    private ?int $maxTablesLarge = 4;

    #[ORM\Column(type: 'integer', nullable: true, options: ["default" => 30])]
    private ?int $timeSlotDuration = 30;

    #[ORM\Column(type: 'integer', nullable: true, options: ["default" => 10])]
    private ?int $maxReservationsPerSlot = 10;

    #[ORM\Column(type: 'integer', nullable: true, options: ["default" => 15])]
    private ?int $bufferBetweenSlots = 15;

    public function __construct()
    {
        // Initialize timestamps
        $now = new \DateTime();
        $this->update_date = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function setFacebookUrl(?string $facebookUrl): static
    {
        $this->facebookUrl = $facebookUrl;

        return $this;
    }

    public function getInstagramUrl(): ?string
    {
        return $this->instagramUrl;
    }

    public function setInstagramUrl(?string $instagramUrl): static
    {
        $this->instagramUrl = $instagramUrl;

        return $this;
    }

    public function getTripadvisorUrl(): ?string
    {
        return $this->tripadvisorUrl;
    }

    public function setTripadvisorUrl(?string $tripadvisorUrl): static
    {
        $this->tripadvisorUrl = $tripadvisorUrl;

        return $this;
    }

    public function getAveragePriceLunch(): ?string
    {
        return $this->averagePriceLunch;
    }

    public function setAveragePriceLunch(?string $averagePriceLunch): static
    {
        $this->averagePriceLunch = $averagePriceLunch;

        return $this;
    }

    public function getAveragePriceDinner(): ?string
    {
        return $this->averagePriceDinner;
    }

    public function setAveragePriceDinner(?string $averagePriceDinner): static
    {
        $this->averagePriceDinner = $averagePriceDinner;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLogoFilename(): ?string
    {
        return $this->logoFilename;
    }

    public function setLogoFilename(?string $logoFilename): static
    {
        $this->logoFilename = $logoFilename;

        return $this;
    }

    public function isDisplayOpeningHours(): ?bool
    {
        return $this->displayOpeningHours;
    }

    public function setDisplayOpeningHours(?bool $displayOpeningHours): static
    {
        $this->displayOpeningHours = $displayOpeningHours;

        return $this;
    }

    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->update_date;
    }

    public function setUpdateDate(\DateTimeInterface $update_date): static
    {
        $this->update_date = $update_date;

        return $this;
    }

    public function getFullAddress(): string
    {
        $parts = [];

        if ($this->address) $parts[] = $this->address;
        if ($this->zipCode && $this->city) $parts[] = $this->zipCode . ' ' . $this->city;
        elseif ($this->city) $parts[] = $this->city;

        return implode(', ', $parts);
    }

    public function getMapUrl(): ?string
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2794.3218692058825!2d{$this->longitude}!3d{$this->latitude}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMzLCsDU4JzA3LjYiTiA5NsKwMTUnMDcuMiJF!5e0!3m2!1sen!2sfr!4v1665404142330!5m2!1sen!2sfr";
        } elseif ($this->address) {
            $address = urlencode($this->getFullAddress());
            return "https://maps.google.com/maps?q={$address}&t=&z=15&ie=UTF8&iwloc=&output=embed";
        }

        return null;
    }

    public function getMaxCapacity(): ?int
    {
        return $this->maxCapacity;
    }

    public function setMaxCapacity(?int $maxCapacity): self
    {
        $this->maxCapacity = $maxCapacity;
        return $this;
    }

    public function getMaxTablesSmall(): ?int
    {
        return $this->maxTablesSmall;
    }

    public function setMaxTablesSmall(?int $maxTablesSmall): self
    {
        $this->maxTablesSmall = $maxTablesSmall;
        return $this;
    }

    public function getMaxTablesMedium(): ?int
    {
        return $this->maxTablesMedium;
    }

    public function setMaxTablesMedium(?int $maxTablesMedium): self
    {
        $this->maxTablesMedium = $maxTablesMedium;
        return $this;
    }

    public function getMaxTablesLarge(): ?int
    {
        return $this->maxTablesLarge;
    }

    public function setMaxTablesLarge(?int $maxTablesLarge): self
    {
        $this->maxTablesLarge = $maxTablesLarge;
        return $this;
    }

    public function getTimeSlotDuration(): ?int
    {
        return $this->timeSlotDuration;
    }

    public function setTimeSlotDuration(?int $timeSlotDuration): self
    {
        $this->timeSlotDuration = $timeSlotDuration;
        return $this;
    }

    public function getMaxReservationsPerSlot(): ?int
    {
        return $this->maxReservationsPerSlot;
    }

    public function setMaxReservationsPerSlot(?int $maxReservationsPerSlot): self
    {
        $this->maxReservationsPerSlot = $maxReservationsPerSlot;
        return $this;
    }

    public function getBufferBetweenSlots(): ?int
    {
        return $this->bufferBetweenSlots;
    }

    public function setBufferBetweenSlots(?int $bufferBetweenSlots): self
    {
        $this->bufferBetweenSlots = $bufferBetweenSlots;
        return $this;
    }
}
