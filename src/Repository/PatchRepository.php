<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Patch;
use App\Entity\Category;
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

    protected function patchesView()
    {
        $sql = 'SELECT patch.*, (
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

        FROM patch';

        return "($sql) patch";
    }

    public function getPatchesToTag(?User $user, Category $category, $n = 16, $count = false)
    {
        $view = $this->patchesView();
        $em = $this->getEntityManager();

        $what = $count ? 'COUNT(*) nb' : 'patch.*';
        $order = $count ? '' : "ORDER BY RAND() DESC LIMIT $n";
        $filterUser = $user ? 'tag.id IS NULL' : '1';

        $stmt = $em->getConnection()->prepare(
            "SELECT $what FROM $view
            LEFT JOIN tag ON (tag.user_id = :user AND tag.patch_id = patch.id)
            WHERE patch.category_id = :category
            AND $filterUser
            $order
            "
        );

        $stmt->execute([
            'category' => $category->getId(),
            'user' => $user ? $user->getId() : null
        ]);

        if ($count) {
            $result = $stmt->fetch();
            return $result['nb'];
        } else {
            return $stmt->fetchAll();
        }
    }

    public function getPatchesSent(User $user, array $patches)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT patch p
            FROM App:Patch patch
            LEFT JOIN patch.tags tag
            WITH :user = tag.user
            WHERE tag.id IS NULL
            AND patch.id in (:patches)
            ');

        $query->setParameter('user', $user);
        $query->setParameter('patches', $patches);

        return $query->getResult();
    }
}
