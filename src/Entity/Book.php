<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: "string", length: 255)]
    private ?string $bookId;

    #[ORM\Column(type: "string", length: 255)]
    private ?string $title;

    #[ORM\OneToMany(mappedBy: "book", targetEntity: Hymn::class, orphanRemoval: true)]
    private ?object $hymns;

    #[ORM\Column(type: "integer")]
    private ?int $totalSongs;

    public function __construct()
    {
        $this->hymns = new ArrayCollection();
    }

    public function getBookId(): ?string
    {
        return $this->bookId;
    }

    public function setBookId(?string $bookId): self
    {
        $this->bookId = $bookId;

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

    /**
     * @return Collection<int, Hymn>
     */
    public function getHymns(): Collection
    {
        return $this->hymns;
    }

    public function addHymn(Hymn $hymn): self
    {
        if (!$this->hymns->contains($hymn)) {
            $this->hymns[] = $hymn;
            $hymn->setBook($this);
        }

        return $this;
    }

    public function removeHymn(Hymn $hymn): self
    {
        if ($this->hymns->removeElement($hymn)) {
            // set the owning side to null (unless already changed)
            if ($hymn->getBook() === $this) {
                $hymn->setBook(null);
            }
        }

        return $this;
    }

    public function getTotalSongs(): ?int
    {
        return $this->totalSongs;
    }

    public function setTotalSongs(int $totalSongs): self
    {
        $this->totalSongs = $totalSongs;

        return $this;
    }

    public function __toString(): string
    {
        return $this->bookId;
    }
}
