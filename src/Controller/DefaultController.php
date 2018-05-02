<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Repository\PatchRepository;
use App\Repository\CategoryRepository;
use App\Entity\User;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function index(CategoryRepository $categories, PatchRepository $patches)
    {
        $infos = $categories->getCategories();

        foreach ($infos as &$info) {
            $cat = $categories->find($info['id']);
            $info['category'] = $cat;
            $info['toTag'] = $patches->getPatchesToTag(null, $cat, 0, true);
            $info['toTagUser'] = $patches->getPatchesToTag($this->getUser(), $cat, 0, true);
            $info['toTagTeam'] = $patches->getPatchesToTag(null, $cat, 0, true, true);
        }

        return $this->render('default/index.html.twig', [
            'categories' => $infos
        ]);
    }

    /**
     * @Route("/unsuscribe/{user}/{token}", name="unsuscribe")
     */
    public function unsuscribe(User $user, string $token)
    {
        $ok = false;

        if ($user->getUnsuscribeToken() == $token) {
            $em = $this->getDoctrine()->getManager();
            $user->setAcceptNotifications(false);
            $em->flush();
            $ok = true;
        }

        return $this->render('default/unsuscribe.html.twig', [
            'ok' => $ok
        ]);
    }
}
