<?php

namespace App\Repository;

use App\Entity\Sequence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Sequence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sequence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sequence[]    findAll()
 * @method Sequence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SequenceRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Sequence::class);
    }

    public function getAll()
    {
        $em = $this->getEntityManager();

        $stmt = $em->getConnection()->prepare(
            "SELECT sequence.*, sequence.session_id as sessionId,
            (SELECT COUNT(*) FROM patch
             WHERE patch.sequence_id = sequence.id) patches
            FROM sequence
            ORDER BY date_creation DESC
            "
        );

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function untag(Sequence $sequence)
    {
        $em = $this->getEntityManager();

        $stmt = $em->getConnection()->prepare(
            "DELETE tag FROM tag
            JOIN patch ON tag.patch_id = patch.id
            WHERE patch.sequence_id = :sequence
            "
        );

        $stmt->execute([
            'sequence' => $sequence->getId()
        ]);

        $stmt = $em->getConnection()->prepare(
            "UPDATE patch
            SET
            patch.consensus=false,
            patch.votes=0,
            patch.votes_yes=0,
            patch.votes_no=0,
            patch.votes_unknown=0,
            patch.value=2
            WHERE patch.sequence_id = :sequence
            "
        );

        $stmt->execute([
            'sequence' => $sequence->getId()
        ]);
    }
}
