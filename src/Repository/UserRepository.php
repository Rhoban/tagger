<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\Patch;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getAll()
    {
        $em = $this->getEntityManager();
        $stmt = $em->getConnection()->prepare(
            'SELECT users.*, (SELECT COUNT(*) FROM tag WHERE tag.user_id = users.id) tags
            FROM users
            '
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getLeaderboard()
    {
        $em = $this->getEntityManager();
        $stmt = $em->getConnection()->prepare(
            'SELECT users.*,
            (SELECT COUNT(*) FROM tag
            JOIN patch ON tag.patch_id = patch.id
            WHERE tag.user_id = users.id AND patch.consensus)
            score,

            (SELECT COUNT(*) FROM tag
            JOIN patch ON tag.patch_id = patch.id
            WHERE tag.user_id = users.id
            AND patch.consensus
            AND patch.value != tag.value
            )
            disagree

            FROM users
            ORDER BY score DESC
            LIMIT 25
            '
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
