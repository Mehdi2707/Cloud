<?php

namespace App\Controller;

use App\Entity\UploadedFiles;
use App\Form\UploadedFilesFormType;
use App\Message\FileUploadMessage;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Serializable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, MessageBusInterface $messageBus, SluggerInterface $slugger, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if(!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        if(!$user->getIsValid())
            return $this->redirectToRoute('app_welcome');

        $uploadedFile = new UploadedFiles();
        $form = $this->createForm(UploadedFilesFormType::class, $uploadedFile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $file = $form->get('name')->getData();
            $filesystem = new Filesystem();

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    if(!$filesystem->exists('/home/mehdi/'.$user->getUsername()))
                        $filesystem->mkdir('/home/mehdi/'.$user->getUsername());

                } catch (FileException $e) {
                    dd($e);
                }

                $uploadedFile->setName($newFilename);
                $uploadedFile->setOriginalName($originalFilename);
                $uploadedFile->setUser($user);
            }

            $entityManager->persist($uploadedFile);
            $entityManager->flush();

            $messageBus->dispatch(
                new FileUploadMessage($newFilename, $user->getId())
            );

            //$fileUploadService->uploadFile($file, $user, $uploadedFile);
            // Vous n'avez pas besoin de gérer la réponse ici car le traitement se fait en arrière-plan.
            $this->addFlash('success', 'Fichier uploadé en arrière-plan');
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
