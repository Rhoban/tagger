<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Patch;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PatchRepository")
 * @ORM\Table(indexes={@ORM\Index(name="consensus", columns={"consensus"})})
 * @ORM\Table(indexes={@ORM\Index(name="votes", columns={"votes"})})
 */
class Patch
{
    /**
     * Minimum number of users needed to have the consensus
     */
    static public $consensusMinUsers = 1;

    /**
     * Ratio of vote for (yes, no or unknown) to gain consensus
     */
    static public $consensusThreshold = 0.6;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Sequence", inversedBy="patches")
     * @ORM\JoinColumn(nullable=false)
     */
    private $sequence;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="patches")
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $filename;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Tag", mappedBy="patch", orphanRemoval=true)
     */
    private $tags;

    /**
     * @ORM\Column(type="integer")
     */
    private $votesYes;

    /**
     * @ORM\Column(type="integer")
     */
    private $votesNo;

    /**
     * @ORM\Column(type="integer")
     */
    private $votesUnknown;

    /**
     * @ORM\Column(type="integer")
     */
    private $votes;

    /**
     * @ORM\Column(type="boolean")
     */
    private $consensus;

    /**
     * @ORM\Column(type="integer")
     */
    private $value;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->value = 2;
        $this->votes = 0;
        $this->votesYes = 0;
        $this->votesNo = 0;
        $this->votesUnknown = 0;
        $this->consensus = false;

    }

    public function getId()
    {
        return $this->id;
    }

    public function getSequence(): ?Sequence
    {
        return $this->sequence;
    }

    public function setSequence(?Sequence $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getFullFilename(): string
    {
        return WEB_DIRECTORY.'/'.$this->getFilename();
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
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
            $tag->setPatch($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
            // set the owning side to null (unless already changed)
            if ($tag->getPatch() === $this) {
                $tag->setPatch(null);
            }
        }

        return $this;
    }

    public function getVotesYes(): ?int
    {
        return $this->votesYes;
    }

    public function setVotesYes(int $votesYes): self
    {
        $this->votesYes = $votesYes;

        return $this;
    }

    public function getVotesNo(): ?int
    {
        return $this->votesNo;
    }

    public function setVotesNo(int $votesNo): self
    {
        $this->votesNo = $votesNo;

        return $this;
    }

    public function getVotesUnknown(): ?int
    {
        return $this->votesUnknown;
    }

    public function setVotesUnknown(int $votesUnknown): self
    {
        $this->votesUnknown = $votesUnknown;

        return $this;
    }

    public function getVotes(): ?int
    {
        return $this->votes;
    }

    public function setVotes(int $votes): self
    {
        $this->votes = $votes;

        return $this;
    }

    public function getConsensus(): ?bool
    {
        return $this->consensus;
    }

    public function setConsensus(bool $consensus): self
    {
        $this->consensus = $consensus;

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

    public function resetVotes()
    {
        $this->votes = 0;
        $this->votesYes = 0;
        $this->votesNo = 0;
        $this->votesUnknown = 0;
        $this->consensus = false;
    }

    public function updateConsensus()
    {
        $this->value = 2;
        $this->consensus = false;

        if ($this->votes >= self::$consensusMinUsers) {
            $maxVotes = max($this->votesNo, $this->votesYes, $this->votesUnknown);

            if ($maxVotes/$this->votes >= self::$consensusThreshold) {
                if ($maxVotes == $this->votesNo) $this->value = 0;
                else if ($maxVotes == $this->votesYes) $this->value = 1;
                else if ($maxVotes == $this->votesUnknown) $this->value = 2;
                $this->consensus = true;
            }
        }
    }

    public function applyValue(Tag $tag, $delta = 1)
    {
        $tmp = $this->getVotes();

        if ($tag->getValue() == 0) {
            $this->votesNo = max(0, $this->votesNo + $delta);
        } else if ($tag->getValue() == 1) {
            $this->votesYes = max(0, $this->votesYes + $delta);
        } else if ($tag->getValue() == 2) {
            $this->votesUnknown = max(0, $this->votesUnknown + $delta);
        }
        $this->setVotes(max(0, $this->votesNo + $this->votesYes + $this->votesUnknown));
    }

    public function apply(Tag $tag)
    {
        $this->applyValue($tag);

        $this->updateConsensus();
    }

    public function cancel(Tag $tag)
    {
        $this->applyValue($tag, -1);

        $this->updateConsensus();
    }

    public function recompute()
    {
        $this->resetVotes();

        foreach ($this->getTags() as $tag) {
            $this->applyValue($tag);
        }
        $this->updateConsensus();
    }
}
