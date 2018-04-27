<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Training;
use App\Form\UserType;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\UserBundle\Model\UserManagerInterface;

/**
 * @Route("/user")
 */
class UserController extends Controller
{
    /**
     * @Route("/", name="user_index", methods="GET")
     */
    public function index(): Response
    {
        $users = $this->getDoctrine()
            ->getRepository(User::class)
            ->getAll();

        return $this->render('user/index.html.twig', ['users' => $users]);
    }

    /**
     * @Route("/new", name="user_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods="GET")
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', ['user' => $user]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods="GET|POST")
     */
    public function edit(Request $request, User $user, UserManagerInterface $userManager, CategoryRepository $categories): Response
    {
        $categoryChoices = [];
        $indexToCategory = [];
        $k = 0;
        $em = $this->getDoctrine()->getManager();
        foreach ($categories->findAll() as $category) {
            $categoryChoices[$category->getName()] = $category->getName();
            $indexToCategory[$category->getName()] = $category;
            $training = $user->trainingFor($category);

            if ($training) {
                if ($training->getTrained()) {
                    $user->trainedCategories[] = $category->getName();
                }
            } else {
                $training = new Training;
                $training
                    ->setUser($user)
                    ->setCategory($category)
                    ;
                $em->persist($training);
            }
            $k++;
        }
        $em->flush();

        $form = $this->createForm(UserType::class, $user, [
            'categories' => $categoryChoices
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($indexToCategory as $k => $category) {
                $training = $user->trainingFor($category);
                if (in_array($k, $user->trainedCategories)) {
                    $training->setTrained(true);
                } else {
                    $training->setTrained(false)->setScore(0);
                }
            }

            if ($user->getPlainPassword()) {
                $userManager->updateUser($user);
            }
            $em->flush();

            return $this->redirectToRoute('user_edit', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods="DELETE")
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * @Route("/untag/{id}", name="user_untag")
     */
    public function untag(User $user): Response
    {
        $em = $this->getDoctrine()->getManager();

        foreach ($user->getTags() as $tag) {
            $tag->cancel();
            $em->remove($tag);
        }
        $em->flush();

        return $this->redirectToRoute('user_index');
    }
}
