<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TagRepository")
 * @ORM\Table(indexes={@ORM\Index(name="value", columns={"value"})})
 */
class Tag
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Patch", inversedBy="tags")
     * @ORM\JoinColumn(nullable=false)
     */
    private $patch;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="tags")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer")
     *
     * 0: no
     * 1: yes
     * 2: unknown
     */
    private $value;

    public function getId()
    {
        return $this->id;
    }

    public function getPatch(): ?Patch
    {
        return $this->patch;
    }

    public function setPatch(?Patch $patch): self
    {
        $this->patch = $patch;

        return $this;
    }

    public function apply()
    {
        $this->getPatch()->apply($this);
    }

    public function cancel()
    {
        $this->getPatch()->cancel($this);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }
}
