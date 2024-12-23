<?php

namespace App\Entity;

use App\Repository\VerseRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VerseRepository::class)
 */
class Verse
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $verseId;

    /**
     * @ORM\ManyToOne(targetEntity=Hymn::class, inversedBy="verses")
     * @ORM\JoinColumn(nullable=false, referencedColumnName="hymn_id")
     */
    private ?Hymn $hymn;

    /**
     * @ORM\Column(type="smallint")
     */
    private ?int $position;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $isChorus;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private ?string $lyrics;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $chords;

    public function getVerseId(): ?int
    {
        return $this->verseId;
    }

    public function getHymn(): ?Hymn
    {
        return $this->hymn;
    }

    public function setHymn(?Hymn $hymn): self
    {
        $this->hymn = $hymn;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function isChorus(): ?bool
    {
        return $this->isChorus;
    }

    public function setChorus(bool $isChorus): self
    {
        $this->isChorus = $isChorus;

        return $this;
    }

    public function getLyrics(): ?string
    {
        return $this->lyrics;
    }

    public function setLyrics(string $lyrics): self
    {
        $this->lyrics = $lyrics;

        return $this;
    }

    public function getChords(): ?string
    {
        return $this->chords;
    }

    public function setChords(string $chords): self
    {
        $this->chords = $chords;

        return $this;
    }
}
