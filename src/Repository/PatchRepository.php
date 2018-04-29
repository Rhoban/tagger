<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Patch;
use App\Entity\Category;
use App\Entity\Sequence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PatchRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Patch::class);
    }

    public function getPatchesInfos(Category $category, ?Sequence $sequence = null, $consensus = true)
    {
        $em = $this->getEntityManager();

        if ($sequence) {
            $condition = $sequence ? 'AND patch.sequence_id = :sequence' : '';
        } else {
            $condition = 'AND session.enabled';
        }

        if ($consensus) {
            $condition .= ' AND patch.consensus';
        }

        $stmt = $em->getConnection()->prepare(
            "SELECT patch.* FROM patch
            JOIN sequence ON patch.sequence_id = sequence.id
            JOIN session ON sequence.session_id = session.id
            WHERE patch.category_id = :category
            $condition
            "
        );

        $params = [
            'category' => $category->getId()
        ];
        if ($sequence) {
            $params['sequence'] = $sequence->getId();
        }

        $stmt->execute($params);
        $tmp = $stmt->fetchAll();
        $infos = [
            'patches' => [
                'yes' => [], 'no' => [], 'unknown' => [],
            ],
            'count' => count($tmp)
        ];

        $values = [
            0 => 'no', 1 => 'yes', 2 => 'unknown'
        ];
        foreach ($tmp as $patch) {
            $patch['bestValueTags'] = max($patch['votes_yes'], $patch['votes_no'], $patch['votes_unknown']);

            $value = $patch['consensus'] ? $patch['value'] : 2;
            $infos['patches'][$values[$value]][] = $patch;
        }


        return $infos;
    }

    /**
     * Getting patches to tag
     *
     * @param  ?User    $user     The user that is tagging, or null if we want for everyone
     * @param  Category $category Category
     * @param  integer  $n        Number of tags we want
     * @param  boolean  $count    Do we want the count or to retrieve ?
     */
    public function getPatchesToTag(?User $user, Category $category, $n = 16, $count = false, $noConsensus = false)
    {
        $em = $this->getEntityManager();

        $what = $count ? 'COUNT(*) nb' : 'patch.*';
        $order = $count ? '' : "ORDER BY consensus ASC, IF(votes>0,1,0) DESC, RAND() DESC LIMIT $n";
        $filterUser = $user ? 'patch.id NOT IN (SELECT tag.patch_id FROM tag WHERE tag.user_id = :user)' : 'true';
        $hasConsensus = $noConsensus ? 'AND NOT consensus' : '';

        $stmt = $em->getConnection()->prepare(
            "SELECT $what FROM patch
            JOIN sequence ON patch.sequence_id = sequence.id
            JOIN session ON sequence.session_id = session.id
            WHERE patch.category_id = :category
            AND session.enabled
            AND $filterUser
            $hasConsensus
            $order
            "
        );

        $params = [
            'category' => $category->getId()
        ];
        if ($user) {
            $params['user'] = $user->getId();
        }
        $stmt->execute($params);

        if ($count) {
            $result = $stmt->fetch();
            return $result['nb'];
        } else {
            return $stmt->fetchAll();
        }
    }

    /**
     * [getTrainingPatches description]
     *
     * @param  User     $user     [description]
     * @param  Category $category [description]
     * @param  integer  $n        [description]
     * @return [type]             [description]
     */
    public function getTrainingPatches(User $user, Category $category, $n = 16)
    {
        $em = $this->getEntityManager();

        $stmt = $em->getConnection()->prepare(
            "SELECT patch.* FROM patch
            JOIN sequence ON patch.sequence_id = sequence.id
            JOIN session ON sequence.session_id = session.id
            WHERE session.training
            AND patch.category_id = :category
            AND patch.consensus
            ORDER BY RAND() LIMIT $n
            "
        );

        $stmt->execute([
            'category' => $category->getId()
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Get all the patches from the list that are not yet tagged by the user,
     * to be sure that we are allowed to tag it after
     */
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
