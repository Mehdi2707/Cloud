<?php

namespace App\Controller;

use App\Entity\UploadedFiles;
use App\Form\UploadedFilesFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if(!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        if(!$user->getIsValid())
            return $this->redirectToRoute('app_welcome');

        $filesystem = new Filesystem();
        $uploadedFile = new UploadedFiles();
        $form = $this->createForm(UploadedFilesFormType::class, $uploadedFile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFiles $file */
            $file = $form->get('name')->getData();

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    if(!$filesystem->exists('/home/mehdi/'.$user->getUsername()))
                        $filesystem->mkdir('/home/mehdi/'.$user->getUsername());

                    $file->move(
                        '/home/mehdi/'.$user->getUsername(),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $uploadedFile->setName($newFilename);
                $uploadedFile->setOriginalName($originalFilename);
                $uploadedFile->setUser($user);
            }

            $entityManager->persist($uploadedFile);
            $entityManager->flush();

            $this->addFlash('success', 'Fichier uploadé');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
            'files' => $user->getUploadedFiles()
        ]);
    }

    #[Route('/bienvenue', name: 'app_welcome')]
    public function welcome(): Response
    {
        return $this->render('home/welcome.html.twig', [

        ]);
    }
}
