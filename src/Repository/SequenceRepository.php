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

//    /**
//     * @return Sequence[] Returns an array of Sequence objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Sequence
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
