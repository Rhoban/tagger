<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Repository\UserRepository;
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
     * @Route("/unsubscribe/{user}/{token}", name="unsubscribe")
     */
    public function unsubscribe(User $user, string $token)
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

    /**
     * @Route("/leaderboard", name="leaderboard")
     */
    public function leaderboard(UserRepository $usersRepository)
    {
        $users = $usersRepository->getLeaderboard();

        return $this->render('default/leaderboard.html.twig', [
            'users' => $users
        ]);
    }
}
