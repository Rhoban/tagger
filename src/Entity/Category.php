<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
 */
class Category
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
     * @ORM\OneToMany(targetEntity="App\Entity\Patch", mappedBy="category", orphanRemoval=true)
     */
    private $patches;

    public function __construct()
    {
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

    public function __toString(): ?string
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
            $patch->setCategory($this);
        }

        return $this;
    }

    public function removePatch(Patch $patch): self
    {
        if ($this->patches->contains($patch)) {
            $this->patches->removeElement($patch);
            // set the owning side to null (unless already changed)
            if ($patch->getCategory() === $this) {
                $patch->setCategory(null);
            }
        }

        return $this;
    }

    public function unlinkPatches()
    {
        foreach ($this->getPatches() as $patch) {
            unlink($patch->getFullFilename());
        }
    }
}
