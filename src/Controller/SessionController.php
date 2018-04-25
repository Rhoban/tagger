<?php

namespace App\Controller;

use App\Entity\Patch;
use App\Entity\Session;
use App\Entity\Sequence;
use App\Entity\Category;
use App\Form\SessionType;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Session\Session as SfSession;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @Route("/session")
 */
class SessionController extends Controller
{
    /**
     * @Route("/", name="session_index", methods="GET")
     */
    public function index(SessionRepository $sessionRepository): Response
    {
        return $this->render('session/index.html.twig', ['sessions' => $sessionRepository->findAll()]);
    }

    /**
     * @Route("/new", name="session_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $session = new Session();
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($session);
            $em->flush();

            return $this->redirectToRoute('session_index');
        }

        return $this->render('session/new.html.twig', [
            'session' => $session,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="session_show", methods="GET")
     */
    public function show(Session $session): Response
    {
        return $this->render('session/show.html.twig', ['session' => $session]);
    }

    /**
     * @Route("/{id}/edit", name="session_edit", methods="GET|POST")
     */
    public function edit(Request $request, Session $session): Response
    {
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('session_edit', ['id' => $session->getId()]);
        }

        return $this->render('session/edit.html.twig', [
            'session' => $session,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="session_delete", methods="DELETE")
     */
    public function delete(Request $request, Session $session): Response
    {
        if ($this->isCsrfTokenValid('delete'.$session->getId(), $request->request->get('_token'))) {
            foreach ($session->getSequences() as $sequence) {
                $sequence->unlinkPatches();
            }
            $em = $this->getDoctrine()->getManager();
            $em->remove($session);
            $em->flush();
        }

        return $this->redirectToRoute('session_index');
    }

    protected function importDirectory(string $directory, array &$imported, $last = null, $prefix = [])
    {
        $manager = $this->getDoctrine()->getManager();
        $categories = $manager->getRepository(Category::class);

        foreach (scandir($directory) as $entry) {
            if ($entry != '.' && $entry != '..') {
                $fullName = $directory.'/'.$entry;

                if (is_dir($fullName)) {
                    $newPrefix = $prefix;
                    if ($last) {
                        $newPrefix[] = $last;
                    }
                    $this->importDirectory($fullName, $imported, $entry, $newPrefix);
                } else {
                    if ($last) {

                        // Creating the sequence
                        $sequenceName = implode('_', $prefix);
                        if (!isset($imported[$sequenceName])) {
                            $imported[$sequenceName] = new Sequence;
                            $imported[$sequenceName]
                                ->setName($sequenceName)
                                ;
                            $manager->persist($imported[$sequenceName]);
                        }
                        $sequence = $imported[$sequenceName];

                        // Fetching the category
                        $category = $categories->findOneBy(['name' => $last]);
                        if (!$category) {
                            throw new \RuntimeException('Unknown category: "'.$last.'"');
                        }

                        // Creating the patch
                        $patch = new Patch;
                        $patch->setFilename('uploads/'.$last.'/'.uniqid('', true).'.png');
                        $patch->setCategory($category);
                        $sequence->addPatch($patch);
                        $manager->persist($patch);

                        $filesystem = $this->get('filesystem');
                        $filesystem->copy($fullName, $patch->getFullFilename());
                    }
                }
            }
        }
    }

    /**
     * @Route("/{id}/upload", name="session_upload")
     */
    public function upload(Request $request, Session $session, SfSession $sess, Filesystem $filesystem): Response
    {
        $builder = $this->createFormBuilder();

        $token = uniqid();
        $badArchive = false;
        $imported = null;
        $importError = null;

        $form = $builder->getForm();
        $form
            ->add('archive', FileType::class)
            ->add('Upload', SubmitType::class)
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();
            $sfFile = $data['archive'];
            $filepath = $sfFile->getRealPath();

            $archive = new \ZipArchive;
            if ($archive->open($filepath) === true) {
                $tempDir = sys_get_temp_dir().'/'.uniqid();
                $filesystem->mkdir($tempDir);

                $archive->extractTo($tempDir);
                $archive->close();

                $imported = [];
                try {
                    $this->importDirectory($tempDir, $imported);
                    foreach ($imported as $sequence) {
                        $sequence->setSession($session);
                    }
                    $manager = $this->getDoctrine()->getManager();
                    $manager->flush();
                } catch (\RuntimeException $e) {
                    $imported = null;
                    $importError = $e->getMessage();
                }

                $filesystem->remove($tempDir);
            } else {
                $badArchive = true;
            }
        }

        return $this->render('session/upload.html.twig', [
            'progress_name' => ini_get('session.upload_progress.name'),
            'session' => $session,
            'token' => $token,
            'form' => $form->createView(),
            'imported' => $imported,
            'importError' => $importError,
            'badArchive' => $badArchive
        ]);
    }

    /**
     * @Route("/upload_progress/{token}", name="session_upload_progress")
     */
    public function uploadProgress(string $token, Request $request, SfSession $sess)
    {
        // XXX: We need here to access directly the $_SESSION from php to get the upload
        // status

        $key = ini_get('session.upload_progress.prefix').$token;
        $data = isset($_SESSION[$key]) ? $_SESSION[$key] : [];

        return new JsonResponse($data);
    }
}
