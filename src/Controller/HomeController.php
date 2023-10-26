<?php

namespace App\Controller;

use App\Entity\UploadedFiles;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if(!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        if(!$user->getIsValid())
            return $this->redirectToRoute('app_welcome');

        return $this->render('home/index.html.twig', [
            'files' => $user->getUploadedFiles()
        ]);
    }

    #[Route('/upload', name: 'app_upload')]
    public function upload(Request $request, FileUploadService $fileUploadService): JsonResponse
    {
        $user = $this->getUser();

        if(!$user->getIsValid())
            return new JsonResponse(['success' => false, 'error' => 'Erreur lors de l\'upload']);

        if ($request->isXmlHttpRequest())
        {
            $file = $request->files->get('file');

            if ($file)
            {
                $result = $fileUploadService->uploadFile($file, $user);

                if ($result)
                    return new JsonResponse($result);
                else
                    return new JsonResponse(['success' => false, 'error' => 'Erreur lors de l\'upload']);
            }
            else
                return new JsonResponse(['success' => false, 'error' => 'Aucun fichier trouvé']);
        }

        return new JsonResponse(['success' => false, 'error' => 'Erreur lors de l\'upload']);
    }

    #[Route('/download/{fileName}', name: 'app_download')]
    public function download($fileName): Response
    {
        $user = $this->getUser();
        if (!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        $filePath = $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $fileName;

        if (file_exists($filePath))
        {
            $response = new Response();
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            $response->setContent(file_get_contents($filePath));

            return $response;
        }
        else
        {
            $this->addFlash('warning', 'Le fichier que vous voulez télécharger n\'existe pas');
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/delete/{fileName}', name: 'app_delete')]
    public function delete(EntityManagerInterface $entityManager, string $fileName): Response
    {
        $user = $this->getUser();
        if (!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        $filePath = $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $fileName;

        if (file_exists($filePath) && is_file($filePath))
        {
            unlink($filePath);

            $uploadedFile = $entityManager->getRepository(UploadedFiles::class)->findOneBy(['name' => $fileName]);

            if ($uploadedFile)
            {
                $entityManager->remove($uploadedFile);
                $entityManager->flush();
            }

            $this->addFlash('success', 'Le fichier a été supprimé avec succès');
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('warning', 'Le fichier que vous voulez supprimer est introuvable');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/view/{fileName}', name: 'app_view')]
    public function view(string $fileName): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Pour accéder à votre espace, vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        $filePath = $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $fileName;

        if (file_exists($filePath)) {
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

            $allowedExtensions = ['txt', 'pdf', 'jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedExtensions))
            {
                if (in_array($fileExtension, ['txt']))
                {
                    $content = file_get_contents($filePath);
                    return new Response($content, 200, ['Content-Type' => 'text/plain']);
                }
                elseif (in_array($fileExtension, ['pdf']))
                {
                    return new BinaryFileResponse($filePath, 200, [], true);
                }
                elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']))
                {
                    return new BinaryFileResponse($filePath, 200, ['Content-Type' => 'image/' . $fileExtension], true);
                }
            }
            else
                $this->addFlash('warning', 'Le type de fichier n\'est pas autorisé');
        }
        else
            $this->addFlash('warning', 'Le fichier que vous voulez afficher est introuvable');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/bienvenue', name: 'app_welcome')]
    public function welcome(): Response
    {
        return $this->render('home/welcome.html.twig', [

        ]);
    }
}
