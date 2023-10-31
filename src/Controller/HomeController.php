<?php

namespace App\Controller;

use App\Entity\UploadedFiles;
use App\Repository\UploadedFilesRepository;
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

        $storage = $user->getStorage();
        $storageUsed = $user->getStorageUsed();

        $storageUsedGo = round($storageUsed / 1073741824, 2);

        if(!$storage)
            $pourcentageStorageUsed = 0;
        else
            $pourcentageStorageUsed = round(($storageUsedGo / $storage) * 100);

        return $this->render('home/index.html.twig', [
            'files' => $user->getUploadedFiles(),
            'storageUsed' => $storageUsedGo,
            'storage' => $storage,
            'pourcentageStorageUsed' => $pourcentageStorageUsed
        ]);
    }

    #[Route('/upload', name: 'app_upload')]
    public function upload(Request $request, FileUploadService $fileUploadService, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        if(!$user->getIsValid())
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'upload', Response::HTTP_BAD_REQUEST]);

        if ($request->isXmlHttpRequest())
        {
            $file = $request->files->get('file');

            if ($file)
            {
                $fileSize = $file->getSize();
                $result = $fileUploadService->uploadFile($file, $user, $fileSize);

                if ($result)
                {
                    $user->setStorageUsed($user->getStorageUsed() + $fileSize);

                    $entityManager->persist($user);
                    $entityManager->flush();

                    return new JsonResponse($result);
                }
                else
                    return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'upload'], Response::HTTP_BAD_REQUEST);
            }
            else
                return new JsonResponse(['success' => false, 'message' => 'Aucun fichier trouvé'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'upload'], Response::HTTP_BAD_REQUEST);
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
    public function delete(EntityManagerInterface $entityManager, string $fileName, UploadedFilesRepository $uploadedFilesRepository): Response
    {
        $user = $this->getUser();
        if (!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        $file = $uploadedFilesRepository->findOneBy(['name' => $fileName]);

        $filePath = $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $file->getName();

        if (file_exists($filePath) && is_file($filePath))
        {
            unlink($filePath);
            $user->setStorageUsed($user->getStorageUsed() - $file->getSize());

            $entityManager->remove($file);
            $entityManager->persist($user);
            $entityManager->flush();

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
