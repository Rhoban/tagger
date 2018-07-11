<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\PatchRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/category")
 */
class CategoryController extends Controller
{
    /**
     * @Route("/", name="category_index", methods="GET")
     */
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->getCategories();
        return $this->render('category/index.html.twig', ['categories' => $categories]);
    }

    /**
     * @Route("/new", name="category_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show/{id}/{consensus}", name="category_show", methods="GET")
     */
    public function show(Category $category, PatchRepository $patches, $consensus = 1): Response
    {
        $infos = $patches->getPatchesInfos($category, null, $consensus);

        return $this->render('category/show.html.twig', [
            'category' => $category,
            'patchesInfo' => $infos,
            'consensus' => $consensus
        ]);
    }

    /**
     * @Route("/{id}/edit", name="category_edit", methods="GET|POST")
     */
    public function edit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('category_edit', ['id' => $category->getId()]);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="category_delete", methods="DELETE")
     */
    public function delete(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $category->unlinkPatches();

            $em = $this->getDoctrine()->getManager();
            $em->remove($category);
            $em->flush();
        }

        return $this->redirectToRoute('category_index');
    }
    
    public function getZip(Category $category, Request $request, PatchRepository $patches)
    {
        $infos = $patches->getPatchesInfos($category, null, true);
        $name = uniqid('', true);
        $zipName = '/archives/'.$category->getName().'_'.date('d_m_Y_H_i_s').'.zip';
        $zip = new \ZipArchive;
        $zip->open(WEB_DIRECTORY.$zipName, \ZipArchive::CREATE);
        $json = [];
        
        foreach ($infos['patches']['yes'] as $patch) {
            $targetName = basename($patch['filename']);
            $json[] = $targetName;
            $zip->addFile(WEB_DIRECTORY.'/'.$patch['filename'], $targetName);
        }
        
        foreach ($infos['patches']['no'] as $patch) {
            $targetName = basename($patch['filename']);
            $zip->addFile(WEB_DIRECTORY.'/'.$patch['filename'], $targetName);
        }
        
        $zip->addFromString('data.json', json_encode($json));
        
        $zip->close();
        
        return $request->getUriForPath($zipName);
    }

    /**
     * @Route("/download/{id}", name="category_download")
     */
    public function download(Category $category, Request $request, PatchRepository $patches): Response
    {
        return new RedirectResponse($this->getZip($category, $request, $patches));
    }
    
    /**
     * @Route("/showLink/{id}", name="category_show_link")
     */
    public function showLink(Category $category, Request $request, PatchRepository $patches): Response
    {
        return $this->render('category/showLink.html.twig', [
            'category' => $category,
            'url' => $this->getZip($category, $request, $patches)
        ]);
    }
}
