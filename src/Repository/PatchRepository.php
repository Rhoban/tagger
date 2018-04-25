<?php

namespace App\Repository;

use App\Entity\Patch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Patch|null find($id, $lockMode = null, $lockVersion = null)
 * @method Patch|null findOneBy(array $criteria, array $orderBy = null)
 * @method Patch[]    findAll()
 * @method Patch[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PatchRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Patch::class);
    }

/*
// XXX Request that includes the consensus criterions

SELECT patch.*, (
    SELECT tag.value
    FROM tag
    WHERE tag.patch_id = patch.id
    GROUP BY tag.value
    ORDER BY COUNT(tag.id) DESC
    LIMIT 1
) bestValue,
(
    SELECT COUNT(*)
    FROM tag
    WHERE tag.patch_id = patch.id
) totalTags,
(
    SELECT COUNT(*)
    FROM tag
    WHERE tag.patch_id = patch.id
    AND tag.value = bestValue
) bestValueCount

FROM patch
 */


//    /**
//     * @return Patch[] Returns an array of Patch objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Patch
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
