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
    static public $consensusMinUsers = 2;
    static public $consensus = 0.6;

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
        ) bestValueTags

        FROM patch';

        return "($sql) patch";
    }

    // SQL Condition to append to only get images that do have a consensus
    public function consensusOkSqlCondition()
    {
        return '(totalTags >= '.self::$consensusMinUsers.' AND (bestValueTags/totalTags) > '.self::$consensus.')';
    }

    // Checks if a patch info has the consensus
    public function patchHasConsensus(array $patch)
    {
        return $patch['totalTags'] >= self::$consensusMinUsers &&
            $patch['bestValueTags']/$patch['totalTags'] > self::$consensus;
    }

    public function getPatchesInfos(Category $category, ?Sequence $sequence = null)
    {
        $view = $this->patchesView();
        $em = $this->getEntityManager();

        if ($sequence) {
            $condition = $sequence ? 'AND patch.sequence_id = :sequence' : '';
        } else {
            $condition = 'AND session.enabled';
        }

        $stmt = $em->getConnection()->prepare(
            "SELECT patch.* FROM $view
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
                'yes' => [],
                'no' => [],
                'unknown' => [],
            ],
            'count' => count($tmp)
        ];

        foreach ($tmp as $patch) {
            if ($this->patchHasConsensus($patch) && $patch['bestValue'] != 2) {
                $infos['patches'][$patch['bestValue'] ? 'yes' : 'no'][] = $patch;
            } else {
                $infos['patches']['unknown'][] = $patch;
            }
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
        $view = $this->patchesView();
        $em = $this->getEntityManager();

        $consensusCond = $this->consensusOkSqlCondition();
        $what = $count ? 'COUNT(*) nb' : 'patch.*';
        $order = $count ? '' : "ORDER BY totalTags DESC, RAND() DESC LIMIT $n";
        $filterUser = $user ? 'tag.id IS NULL' : '1';
        $hasConsensus = $noConsensus ? 'AND NOT '.$consensusCond : '';

        $stmt = $em->getConnection()->prepare(
            "SELECT $what FROM $view
            LEFT JOIN tag ON (tag.user_id = :user AND tag.patch_id = patch.id)
            JOIN sequence ON patch.sequence_id = sequence.id
            JOIN session ON sequence.session_id = session.id
            WHERE patch.category_id = :category
            AND session.enabled
            AND $filterUser
            $hasConsensus
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
