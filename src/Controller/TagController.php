<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\Tag;
use App\Entity\Patch;
use App\Entity\Category;
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
    public function patches(Category $category, PatchRepository $patches, Request $request)
    {
        $matrix = $this->getUser()->patchesMatrix();
        $n = $matrix[0]*$matrix[1];

        $toTagUserNoConsensus = $patches->getPatchesToTag($this->getUser(), $category, 0, true, true);
        if ($toTagUserNoConsensus) {
            $toTag = $patches->getPatchesToTag($this->getUser(), $category, $n, false, true);
        } else {
            $toTag = $patches->getPatchesToTag($this->getUser(), $category, $n);
        }
        $json = [];

        foreach ($toTag as $patch) {
            $json[] = [
                $patch['id'],
                $request->getUriForPath('/'.$patch['filename'])
            ];
        }

        return new JsonResponse($json);
    }

    /**
     * @Route("/tag/send/{category}", name="tag_send")
     */
    public function sendTags(Category $category, Request $request, PatchRepository $patches)
    {
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
