<?php

namespace App\Controller;

use App\Entity\Sequence;
use App\Entity\Session;
use App\Form\SequenceType;
use App\Repository\SequenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/sequence/")
 */
class SequenceController extends Controller
{
    /**
     * @Route("{session}/", name="sequence_index", methods="GET")
     */
    public function index(SequenceRepository $sequenceRepository, Session $session): Response
    {
        return $this->render('sequence/index.html.twig', [
                'sequences' => $sequenceRepository->findBy(['session' => $session]),
                'session' => $session
        ]);
    }

    /**
     * @Route("{session}/new", name="sequence_new", methods="GET|POST")
     */
    public function new(Request $request, Session $session): Response
    {
        $sequence = new Sequence();
        $sequence->setSession($session);
        $form = $this->createForm(SequenceType::class, $sequence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($sequence);
            $em->flush();

            return $this->redirectToRoute('sequence_index');
        }

        return $this->render('sequence/new.html.twig', [
            'sequence' => $sequence,
            'session' => $session,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("{id}/show", name="sequence_show", methods="GET")
     */
    public function show(Sequence $sequence): Response
    {
        return $this->render('sequence/show.html.twig', ['sequence' => $sequence]);
    }

    /**
     * @Route("{id}/edit", name="sequence_edit", methods="GET|POST")
     */
    public function edit(Request $request, Sequence $sequence): Response
    {
        $form = $this->createForm(SequenceType::class, $sequence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('sequence_edit', ['id' => $sequence->getId()]);
        }

        return $this->render('sequence/edit.html.twig', [
            'sequence' => $sequence,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("{session}/{id}", name="sequence_delete", methods="DELETE")
     */
    public function delete(Request $request, Session $session, Sequence $sequence): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sequence->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($sequence);
            $em->flush();
        }

        return $this->redirectToRoute('sequence_index', [
            'session' => $session
        ]);
    }
}