<?php
// src/AppBundle/Entity/User.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Tag", mappedBy="user", orphanRemoval=true)
     */
    private $tags;

    /**
     * @ORM\Column(type="integer")
     */
    private $patchesCol;

    /**
     * @ORM\Column(type="integer")
     */
    private $patchesRow;

    public function __construct()
    {
        parent::__construct();
        $this->tags = new ArrayCollection();
        $this->patchesCol = 4;
        $this->patchesRow = 4;
        // your own logic
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
            $tag->setUser($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
            // set the owning side to null (unless already changed)
            if ($tag->getUser() === $this) {
                $tag->setUser(null);
            }
        }

        return $this;
    }

    public function getPatchesCol(): ?int
    {
        return $this->patchesCol;
    }

    public function setPatchesCol(int $patchesCol): self
    {
        $this->patchesCol = $patchesCol;

        return $this;
    }

    public function getPatchesRow(): ?int
    {
        return $this->patchesRow;
    }

    public function setPatchesRow(int $patchesRow): self
    {
        $this->patchesRow = $patchesRow;

        return $this;
    }

    public function patchesMatrix()
    {
        $col = min(8, max(1, $this->getPatchesCol()));
        $row = min(8, max(1, $this->getPatchesRow()));

        return [$col, $row];
    }
}
