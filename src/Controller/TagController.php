<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\Tag;
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
        $toTag = $patches->getPatchesToTag(null, $category, 0, true);

        return $this->render('tag/index.html.twig', [
            'category' => $category,
            'toTagUser' => $toTagUser,
            'toTag' => $toTag
        ]);
    }

    /**
     * @Route("/tag/patches/{category}", name="tag_patches")
     */
    public function patches(Category $category, PatchRepository $patches, Request $request)
    {
        $toTag = $patches->getPatchesToTag($this->getUser(), $category);
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
            $em->persist($tag);
            $tags[] = $tag;
        }
        $em->flush();

        foreach ($tags as &$tag) {
            $tag = $tag->getId();
        }

        return new JsonResponse([
            $patches->getPatchesToTag($this->getUser(), $category, 0, true),
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
                $em->remove($tag);
            }
        }

        $em->flush();
        return new JsonResponse($patches->getPatchesToTag($this->getUser(), $category, 0, true));
    }
}