<?php
/**
 * Quai Antique Restaurant - Entity Relationship Reference
 * 
 * This file contains code examples for setting up relationships
 * between entities using Doctrine ORM annotations.
 * 
 * Copy and paste these annotations into your actual entity classes.
 * 
 * Note: This file is for reference only and is not loaded by the application.
 */

namespace App\Entity;

/**
 * This is a REFERENCE class only - do not use directly.
 * Copy the relevant methods to your actual entity classes.
 */
class Relations
{
    /* 
     * Example of User to Reservation (One to Many)
     * 
     * In User.php:
     * 
     * @ORM\OneToMany(targetEntity=Reservation::class, mappedBy="user")
     * private Collection $reservations;
     * 
     * public function __construct()
     * {
     *     $this->reservations = new ArrayCollection();
     * }
     * 
     * public function getReservations(): Collection
     * {
     *     return $this->reservations;
     * }
     * 
     * public function addReservation(Reservation $reservation): self
     * {
     *     if (!$this->reservations->contains($reservation)) {
     *         $this->reservations[] = $reservation;
     *         $reservation->setUser($this);
     *     }
     *     return $this;
     * }
     * 
     * public function removeReservation(Reservation $reservation): self
     * {
     *     if ($this->reservations->removeElement($reservation)) {
     *         if ($reservation->getUser() === $this) {
     *             $reservation->setUser(null);
     *         }
     *     }
     *     return $this;
     * }
     * 
     * In Reservation.php:
     * 
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="reservations")
     * @ORM\JoinColumn(nullable=true)
     * private ?User $user = null;
     * 
     * public function getUser(): ?User
     * {
     *     return $this->user;
     * }
     * 
     * public function setUser(?User $user): self
     * {
     *     $this->user = $user;
     *     return $this;
     * }
     */
    
    /*
     * Example of Category to Dish (One to Many)
     * 
     * In Category.php:
     * 
     * @ORM\OneToMany(targetEntity=Dish::class, mappedBy="category")
     * private Collection $dishes;
     * 
     * public function __construct()
     * {
     *     $this->dishes = new ArrayCollection();
     * }
     * 
     * public function getDishes(): Collection
     * {
     *     return $this->dishes;
     * }
     * 
     * public function addDish(Dish $dish): self
     * {
     *     if (!$this->dishes->contains($dish)) {
     *         $this->dishes[] = $dish;
     *         $dish->setCategory($this);
     *     }
     *     return $this;
     * }
     * 
     * public function removeDish(Dish $dish): self
     * {
     *     if ($this->dishes->removeElement($dish)) {
     *         if ($dish->getCategory() === $this) {
     *             $dish->setCategory(null);
     *         }
     *     }
     *     return $this;
     * }
     * 
     * In Dish.php:
     * 
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="dishes")
     * @ORM\JoinColumn(nullable=false)
     * private ?Category $category = null;
     * 
     * public function getCategory(): ?Category
     * {
     *     return $this->category;
     * }
     * 
     * public function setCategory(?Category $category): self
     * {
     *     $this->category = $category;
     *     return $this;
     * }
     */
    
    /*
     * Example of Dish to Allergen (Many to Many)
     * 
     * In Dish.php:
     * 
     * @ORM\ManyToMany(targetEntity=Allergen::class, inversedBy="dishes")
     * private Collection $allergens;
     * 
     * public function __construct()
     * {
     *     $this->allergens = new ArrayCollection();
     * }
     * 
     * public function getAllergens(): Collection
     * {
     *     return $this->allergens;
     * }
     * 
     * public function addAllergen(Allergen $allergen): self
     * {
     *     if (!$this->allergens->contains($allergen)) {
     *         $this->allergens[] = $allergen;
     *     }
     *     return $this;
     * }
     * 
     * public function removeAllergen(Allergen $allergen): self
     * {
     *     $this->allergens->removeElement($allergen);
     *     return $this;
     * }
     * 
     * In Allergen.php:
     * 
     * @ORM\ManyToMany(targetEntity=Dish::class, mappedBy="allergens")
     * private Collection $dishes;
     * 
     * public function __construct()
     * {
     *     $this->dishes = new ArrayCollection();
     * }
     * 
     * public function getDishes(): Collection
     * {
     *     return $this->dishes;
     * }
     * 
     * public function addDish(Dish $dish): self
     * {
     *     if (!$this->dishes->contains($dish)) {
     *         $this->dishes[] = $dish;
     *         $dish->addAllergen($this);
     *     }
     *     return $this;
     * }
     * 
     * public function removeDish(Dish $dish): self
     * {
     *     if ($this->dishes->removeElement($dish)) {
     *         $dish->removeAllergen($this);
     *     }
     *     return $this;
     * }
     */
}
