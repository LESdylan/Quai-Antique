<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToMany(targetEntity: Dish::class, inversedBy: 'menus')]
    private Collection $dishes;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: "menus")]
    #[ORM\JoinColumn(nullable: true)]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: Gallery::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Gallery $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    #[ORM\Column(length: 255, nullable: false, options: ["default" => "main"])]
    private string $mealType = 'main'; // Always initialize with string type and default value

    public function __construct()
    {
        $this->dishes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return Collection<int, Dish>
     */
    public function getDishes(): Collection
    {
        return $this->dishes;
    }

    public function addDish(Dish $dish): static
    {
        if (!$this->dishes->contains($dish)) {
            $this->dishes->add($dish);
        }

        return $this;
    }

    public function removeDish(Dish $dish): static
    {
        $this->dishes->removeElement($dish);

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getImage(): ?Gallery
    {
        return $this->image;
    }

    public function setImage(?Gallery $image): static
    {
        $this->image = $image;
        
        // When setting image, also update the imageFilename from the Gallery entity
        if ($image) {
            $this->setImageFilename($image->getFilename());
        }

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;
        
        return $this;
    }

    public function getMealType(): string // Return type is string, not nullable
    {
        return $this->mealType ?? 'main'; // Ensure we never return null
    }

    public function setMealType(?string $mealType): static
    {
        $this->mealType = $mealType ?: 'main'; // Use default if null is passed

        return $this;
    }
    
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function ensureMealTypeIsSet(): void
    {
        if (empty($this->mealType)) {
            $this->mealType = 'main';
        }
    }
}
