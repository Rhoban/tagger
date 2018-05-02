<?php
// src/AppBundle/Entity/User.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Category;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
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

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Training", mappedBy="user", orphanRemoval=true)
     */
    private $trainings;

    public function __construct()
    {
        parent::__construct();
        
        $this->tags = new ArrayCollection();
        $this->patchesCol = 4;
        $this->patchesRow = 4;
        $this->acceptNotifications = true;
        $this->trainings = new ArrayCollection();
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

    public function isAdmin()
    {
        return in_array('ROLE_ADMIN', $this->getRoles());
    }

    public function setAdmin(bool $admin)
    {
        $roles = ['ROLE_USER'];
        if ($admin) {
            $roles[] = 'ROLE_ADMIN';
        }

        $this->setRoles($roles);
    }

    public function removeTrained(Category $trained): self
    {
        if ($this->trained->contains($trained)) {
            $this->trained->removeElement($trained);
        }

        return $this;
    }

    public function trainingFor(Category $category): ?Training
    {
        foreach ($this->getTrainings() as $training) {
            if ($training->getCategory() == $category) {
                return $training;
            }
        }

        return null;
    }

    public function isTrainedFor(Category $category): bool
    {
        $training = $this->trainingFor($category);

        if ($training) {
            return $training->getTrained();
        }

        return false;
    }

    public function trainProgress(Category $category): float
    {
        $training = $this->trainingFor($category);

        if ($training) {
            return $training->getScore() / 1000.0;
        } else {
            return 0;
        }
    }

    /**
     * @return Collection|Training[]
     */
    public function getTrainings(): Collection
    {
        return $this->trainings;
    }

    public function addTraining(Training $training): self
    {
        if (!$this->trainings->contains($training)) {
            $this->trainings[] = $training;
            $training->setUser($this);
        }

        return $this;
    }

    public function removeTraining(Training $training): self
    {
        if ($this->trainings->contains($training)) {
            $this->trainings->removeElement($training);
            // set the owning side to null (unless already changed)
            if ($training->getUser() === $this) {
                $training->setUser(null);
            }
        }

        return $this;
    }

    public $trainedCategories = [];

    /**
     * @ORM\Column(type="boolean")
     */
    private $acceptNotifications;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $unsuscribeToken;

    public function getAcceptNotifications(): ?bool
    {
        return $this->acceptNotifications;
    }

    public function setAcceptNotifications(bool $acceptNotifications): self
    {
        $this->acceptNotifications = $acceptNotifications;

        return $this;
    }

    public function getUnsuscribeToken(): ?string
    {
        return $this->unsuscribeToken;
    }

    public function setUnsuscribeToken(string $unsuscribeToken): self
    {
        $this->unsuscribeToken = $unsuscribeToken;

        return $this;
    }
}
