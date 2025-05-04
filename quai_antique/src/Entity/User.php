<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $defaultGuestCount = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $allergies = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $dietary_regime = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reservation::class)]
    private Collection $reservations;

    #[ORM\ManyToMany(targetEntity: Allergen::class, inversedBy: 'users')]
    private Collection $allergens;

    #[ORM\ManyToMany(targetEntity: DietaryRegime::class, inversedBy: 'users')]
    private Collection $dietaryRegimes;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->allergens = new ArrayCollection();
        $this->dietaryRegimes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDefaultGuestCount(): ?int
    {
        return $this->defaultGuestCount;
    }

    public function setDefaultGuestCount(?int $defaultGuestCount): static
    {
        $this->defaultGuestCount = $defaultGuestCount;

        return $this;
    }

    public function getAllergies(): ?string
    {
        return $this->allergies;
    }

    public function setAllergies(?string $allergies): static
    {
        $this->allergies = $allergies;

        return $this;
    }

    public function getDietaryRegime(): ?string
    {
        return $this->dietary_regime;
    }

    public function setDietaryRegime(?string $dietary_regime): self
    {
        $this->dietary_regime = $dietary_regime;
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setUser($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getUser() === $this) {
                $reservation->setUser(null);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * @return Collection<int, Allergen>
     */
    public function getAllergens(): Collection
    {
        return $this->allergens;
    }

    public function addAllergen(Allergen $allergen): self
    {
        if (!$this->allergens->contains($allergen)) {
            $this->allergens->add($allergen);
        }

        return $this;
    }

    public function removeAllergen(Allergen $allergen): self
    {
        $this->allergens->removeElement($allergen);
        return $this;
    }

    /**
     * @return Collection<int, DietaryRegime>
     */
    public function getDietaryRegimes(): Collection
    {
        return $this->dietaryRegimes;
    }

    public function addDietaryRegime(DietaryRegime $dietaryRegime): self
    {
        if (!$this->dietaryRegimes->contains($dietaryRegime)) {
            $this->dietaryRegimes->add($dietaryRegime);
        }

        return $this;
    }

    public function removeDietaryRegime(DietaryRegime $dietaryRegime): self
    {
        $this->dietaryRegimes->removeElement($dietaryRegime);
        return $this;
    }
}
