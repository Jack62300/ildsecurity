<?php

namespace App\Entity;

use App\Repository\PhoneCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PhoneCategoryRepository::class)]
#[ORM\Table(name: 'phone_category')]
class PhoneCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, PhoneNumber> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: PhoneNumber::class, cascade: ['persist'], orphanRemoval: false)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $numbers;

    public function __construct()
    {
        $this->numbers = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?: 'Catégorie';
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    /** @return Collection<int, PhoneNumber> */
    public function getNumbers(): Collection { return $this->numbers; }

    public function addNumber(PhoneNumber $number): self
    {
        if (!$this->numbers->contains($number)) {
            $this->numbers->add($number);
            $number->setCategory($this);
        }
        return $this;
    }

    public function removeNumber(PhoneNumber $number): self
    {
        if ($this->numbers->removeElement($number)) {
            if ($number->getCategory() === $this) $number->setCategory(null);
        }
        return $this;
    }
}
