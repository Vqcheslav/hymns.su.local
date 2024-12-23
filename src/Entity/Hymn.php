<?php

namespace App\Entity;

use App\Repository\HymnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=HymnRepository::class)
 */
class Hymn
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ascii_string", length=50)
     */
    private ?string $hymnId;

    /**
     * @ORM\ManyToOne(targetEntity=Book::class, inversedBy="hymns")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="book_id")
     */
    private ?Book $book;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $number;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $category;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private ?string $tone;

    /**
     * @ORM\OneToMany(targetEntity=Verse::class, mappedBy="hymn", orphanRemoval=true)
     */
    private $verses;

    public function __construct()
    {
        $this->verses = new ArrayCollection();
    }

    public function getHymnId(): ?string
    {
        return $this->hymnId;
    }

    public function setId(?string $hymnId): self
    {
        $this->hymnId = $hymnId;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getTone(): ?string
    {
        return $this->tone;
    }

    public function setTone(string $tone): self
    {
        $this->tone = $tone;

        return $this;
    }

    /**
     * @return Collection<int, Verse>
     */
    public function getVerses(): Collection
    {
        return $this->verses;
    }

    public function addVerse(Verse $verse): self
    {
        if (!$this->verses->contains($verse)) {
            $this->verses[] = $verse;
            $verse->setHymn($this);
        }

        return $this;
    }

    public function removeVerse(Verse $verse): self
    {
        if ($this->verses->removeElement($verse)) {
            // set the owning side to null (unless already changed)
            if ($verse->getHymn() === $this) {
                $verse->setHymn(null);
            }
        }

        return $this;
    }
}
