<?php

namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Session|null find($id, $lockMode = null, $lockVersion = null)
 * @method Session|null findOneBy(array $criteria, array $orderBy = null)
 * @method Session[]    findAll()
 * @method Session[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Session::class);
    }

    /**
     * Getting all sequences, with the count of patches
     */
    public function getAll()
    {
        $em = $this->getEntityManager();

        $stmt = $em->getConnection()->prepare(
            "SELECT session.*,
            (SELECT COUNT(*) FROM sequence WHERE sequence.session_id = session.id) sequences,
            (SELECT COUNT(*) FROM patch
             JOIN sequence ON sequence.id = patch.sequence_id
             WHERE sequence.session_id = session.id) patches
            FROM session
            ORDER BY date_creation DESC
            "
        );

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Remove all the tags for the given session
     */
    public function untag(Session $session)
    {
        $em = $this->getEntityManager();

        $stmt = $em->getConnection()->prepare(
            "DELETE tag FROM tag
            JOIN patch ON tag.patch_id = patch.id
            JOIN sequence ON patch.sequence_id = sequence.id
            WHERE sequence.session_id = :session
            "
        );

        $stmt->execute([
            'session' => $session->getId()
        ]);

        $stmt = $em->getConnection()->prepare(
            "UPDATE patch
            JOIN sequence ON patch.sequence_id = sequence.id
            SET
            patch.consensus=false,
            patch.votes=0,
            patch.votes_yes=0,
            patch.votes_no=0,
            patch.votes_unknown=0,
            patch.value=2
            WHERE sequence.session_id = :session
            "
        );

        $stmt->execute([
            'session' => $session->getId()
        ]);
    }
}
