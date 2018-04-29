<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SequenceRepository")
 */
class Sequence
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateCreation;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Session", inversedBy="sequences")
     * @ORM\JoinColumn(nullable=false)
     */
    private $session;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Patch", mappedBy="sequence", orphanRemoval=true)
     */
    private $patches;

    public function __construct()
    {
        $this->dateCreation = new \DateTime;
        $this->patches = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getSessionId()
    {
        return $this->getSession()->getId();
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @return Collection|Patch[]
     */
    public function getPatches(): Collection
    {
        return $this->patches;
    }

    public function addPatch(Patch $patch): self
    {
        if (!$this->patches->contains($patch)) {
            $this->patches[] = $patch;
            $patch->setSequence($this);
        }

        return $this;
    }

    public function removePatch(Patch $patch): self
    {
        if ($this->patches->contains($patch)) {
            $this->patches->removeElement($patch);
            // set the owning side to null (unless already changed)
            if ($patch->getSequence() === $this) {
                $patch->setSequence(null);
            }
        }

        return $this;
    }

    public function unlinkPatches()
    {
        foreach ($this->getPatches() as $patch) {
            @unlink($patch->getFullFilename());
        }
    }

    public function getPatchesByCategory()
    {
        $patches = [];
        foreach ($this->getPatches() as $patch) {
            $category = $patch->getCategory()->getName();
            if (!isset($patches[$category])) {
                $patches[$category] = [];
            }
            $patches[$category][] = $patch;
        }

        return $patches;
    }
}
