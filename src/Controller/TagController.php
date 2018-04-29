<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session as SfSession;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\Tag;
use App\Entity\Patch;
use App\Entity\Category;
use App\Entity\Training;
use App\Repository\TagRepository;
use App\Repository\PatchRepository;

class TagController extends Controller
{
    /**
     * @Route("/tagging/{category}", name="tag")
     */
    public function index(Category $category, PatchRepository $patches)
    {
        $toTagUser = $patches->getPatchesToTag($this->getUser(), $category, 0, true);
        $toTagUserNoConsensus = $patches->getPatchesToTag($this->getUser(), $category, 0, true, true);

        $toTag = $patches->getPatchesToTag(null, $category, 0, true);
        $toTagTeam = $patches->getPatchesToTag(null, $category, 0, true, true);

        return $this->render('tag/index.html.twig', [
            'category' => $category,
            'toTagUser' => $toTagUser,
            'toTagUserNoConsensus' => $toTagUserNoConsensus,
            'toTagTeam' => $toTagTeam,
            'toTag' => $toTag
        ]);
    }

    /**
     * @Route("/tag/patches/{category}", name="tag_patches")
     */
    public function patches(Category $category, PatchRepository $patches, Request $request, SfSession $sess)
    {
        $user = $this->getUser();

        if (!$user->isTrainedFor($category)) {
            return $this->trainingPatches($category, $patches, $request, $sess);
        }

        $matrix = $user->patchesMatrix();
        $n = $matrix[0]*$matrix[1];

        $toTag = $patches->getPatchesToTag($this->getUser(), $category, $n);
        $json = [];

        foreach ($toTag as $patch) {
            $json[] = [
                $patch['id'],
                $request->getUriForPath('/'.$patch['filename'])
            ];
        }

        return new JsonResponse($json);
    }

    public function trainingPatches(Category $category, PatchRepository $patches, Request $request, SfSession $sess)
    {
        $toReview = [];
        $user = $this->getUser();
        $matrix = $user->patchesMatrix();
        $n = $matrix[0]*$matrix[1];

        if (!$user->trainingFor($category)) {
            $training = new Training;
            $training
                ->setUser($user)
                ->setCategory($category);
                ;
            $em = $this->getDoctrine()->getManager();
            $em->persist($training);
            $em->flush();
        }

        $toTag = $patches->getTrainingPatches($user, $category, $n);

        $json = [];

        foreach ($toTag as $patch) {
            $json[] = [
                $patch['id'],
                $request->getUriForPath('/'.$patch['filename'])
            ];
            $toReview[] = $patch['id'];
        }
        $sess->set('toReview', $toReview);

        return new JsonResponse($json);
    }

    /**
     * @Route("/tag/send/{category}", name="tag_send")
     */
    public function sendTags(Category $category, Request $request, PatchRepository $patches)
    {
        $user = $this->getUser();
        if (!$user->isTrainedFor($category)) {
            return new JsonResponse(false);
        }

        $userTags = $request->request->all();
        $tagged = $patches->getPatchesSent($this->getUser(), array_keys($userTags));
        $em = $this->getDoctrine()->getManager();
        $count = 0;
        $tags = [];

        foreach ($tagged as $patch) {
            $patch = $patch['p'];
            $tag = new Tag;
            $tag
                ->setPatch($patch)
                ->setUser($this->getUser())
                ->setValue($userTags[$patch->getId()])
                ;
            $tag->apply();
            $em->persist($tag);
            $tags[] = $tag;
        }
        $em->flush();

        foreach ($tags as &$tag) {
            $tag = $tag->getId();
        }

        return new JsonResponse([
            (int)$patches->getPatchesToTag($this->getUser(), $category, 0, true),
            (int)$patches->getPatchesToTag($this->getUser(), $category, 0, true, true),
            (int)$patches->getPatchesToTag(null, $category, 0, true, true),
            $tags
        ]);
    }

    /**
     * @Route("/tag/review/{category}", name="tag_review")
     * Review tags for patches
     */
    public function reviewTags(Category $category, Request $request, PatchRepository $patches, SfSession $sess)
    {
        $toReview = $sess->get('toReview', []);
        $em = $this->getDoctrine()->getManager();
        $userTags = $request->request->all();
        $training = $this->getUser()->trainingFor($category);

        $json = [
            'progress' => 0,
            'trained' => false,
            'patches' => []
        ];
        foreach ($userTags as $patchId => $tag) {
            if (in_array($patchId, $toReview)) {
                $patch = $patches->find($patchId);
                if ($patch) {
                    if ($patch->getValue() == $tag) {
                        $json['patches'][$patchId] = true;
                        $training->setScore($training->getScore() + 1);
                    } else {
                        $json['patches'][$patchId] = false;
                        $training->setScore($training->getScore() - 25);
                    }
                }
            }
        }
        $em->flush();

        $json['progress'] = $this->getUser()->trainProgress($category);

        if ($json['progress'] >= 1.0) {
            $training->setTrained(true);
            $json['trained'] = true;
            $em->flush();
        }

        return new JsonResponse($json);
    }

    /**
     * @Route("/tag/cancel/{category}", name="tag_cancel")
     */
    public function cancelTags(Category $category, TagRepository $tags, Request $request, PatchRepository $patches)
    {
        $em = $this->getDoctrine()->getManager();
        $cancelTags = $request->request->get('tags');

        foreach ($cancelTags as $tagId) {
            $tag = $tags->find($tagId);
            if ($tag && $tag->getUser() == $this->getUser()) {
                $tag->cancel();
                $em->remove($tag);
            }
        }

        $em->flush();

        return new JsonResponse([
            (int)$patches->getPatchesToTag($this->getUser(), $category, 0, true),
            (int)$patches->getPatchesToTag($this->getUser(), $category, 0, true, true),
            (int)$patches->getPatchesToTag(null, $category, 0, true, true),
        ]);
    }

    /**
     * @Route("/untag", name="untag_patch")
     */
    public function untag(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $patchGet = $request->query->get('patch');

        if ($patch = $em->getRepository(Patch::class)->find($patchGet)) {
            foreach ($patch->getTags() as $tag) {
                $em->remove($tag);
            }
            $patch->resetVotes();
            $em->flush();

            return new JsonResponse('ok');
        } else {
            return new JsonResponse('ko');
        }
    }
}
