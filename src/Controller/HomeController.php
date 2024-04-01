<?php

namespace App\Controller;

use App\Repository\FolderRepository;
use App\Repository\UploadedFilesRepository;
use App\Service\FileUploadService;
use App\Service\FolderService;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(UploadedFilesRepository $uploadedFilesRepository): Response
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
            'folders' => $user->getFolders(),
            'files' => $uploadedFilesRepository->findBy(['folder' => null]),
            'storageUsed' => $storageUsedGo,
            'storage' => $storage,
            'pourcentageStorageUsed' => $pourcentageStorageUsed
        ]);
    }

    #[Route('/folder', name: 'app_folder')]
    public function folder(Request $request, FolderService $folderService): JsonResponse
    {
        $user = $this->getUser();

        if(!$user->getIsValid())
            return new JsonResponse(['success' => false, 'message' => 'Erreur.']);

        if ($request->isXmlHttpRequest())
        {
            $folderName = $request->request->get('folderName');

            if ($folderName)
            {
                $result = $folderService->createFolder($folderName, $user);

                if($result)
                    return new JsonResponse($result);
                else
                    return new JsonResponse(['success' => false, 'message' => 'Un problème est survenue lors de la création du dossier.']);
            }
            else
                return new JsonResponse(['success' => false, 'message' => 'Le nom du dossier n\'est pas valide.']);
        }

        return new JsonResponse(['success' => false, 'message' => 'Un problème est survenue lors de la soumission, veuillez réessayer plus tard.']);
    }

    #[Route('/upload', name: 'app_upload')]
    public function upload(Request $request, FileUploadService $fileUploadService, EntityManagerInterface $entityManager, FolderRepository $folderRepository): JsonResponse
    {
        $user = $this->getUser();

        if(!$user->getIsValid())
            return new JsonResponse(['success' => false, 'message' => 'Erreur']);

        if ($request->isXmlHttpRequest())
        {
            $files = $request->files->get('files');
            $folderName = $request->request->get('folderName');
            $results = [];

            foreach ($files as $file)
            {
                if ($file)
                {
                    $fileSize = $file->getSize();
                    $result = $fileUploadService->uploadFile($file, $user, $fileSize, $folderRepository->findOneBy(['name' => $folderName]));

                    if ($result)
                    {
                        $user->setStorageUsed($user->getStorageUsed() + $fileSize);

                        $entityManager->persist($user);
                        $entityManager->flush();

                        $results[] = $result;
                    }
                    else
                        return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'upload']);
                }
                else
                    return new JsonResponse(['success' => false, 'message' => 'Aucun fichier trouvé']);
            }
            return new JsonResponse($results);
        }

        return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'upload']);
    }

    #[Route('/renameFolder', name: 'app_renameFolder')]
    public function renameFolder(Request $request, FolderService $folderService): Response
    {
        $user = $this->getUser();

        if(!$user->getIsValid())
            return new JsonResponse(['success' => false, 'message' => 'Erreur.']);

        if ($request->isXmlHttpRequest())
        {
            $newFolderName = $request->request->get('newFolderName');
            $oldFolderName = $request->request->get('oldFolderName');

            if ($newFolderName)
            {
                $result = $folderService->updateFolder($oldFolderName, $newFolderName, $user);

                if($result)
                    return new JsonResponse($result);
                else
                    return new JsonResponse(['success' => false, 'message' => 'Un problème est survenue lors du renommage du dossier.']);
            }
            else
                return new JsonResponse(['success' => false, 'message' => 'Le nom du dossier n\'est pas valide.']);
        }

        return new JsonResponse(['success' => false, 'message' => 'Un problème est survenue lors de la soumission, veuillez réessayer plus tard.']);
    }

    #[Route('/deleteFolder/{folderName}', name: 'app_deleteFolder')]
    public function deleteFolder(EntityManagerInterface $entityManager, string $folderName, FolderRepository $folderRepository): Response
    {
        $user = $this->getUser();
        if (!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        $folder = $folderRepository->findOneBy(['name' => $folderName]);

        $filePath = $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $folder->getName();

        if (file_exists($filePath))
        {
            rmdir($filePath);

            $entityManager->remove($folder);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Le dossier a été supprimé avec succès');
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('warning', 'Le dossier que vous voulez supprimer est introuvable');
        return $this->redirectToRoute('app_home');
    }

    #[Route('/viewFolder/{folderName}', name: 'app_viewFolder')]
    public function viewFolder(string $folderName, FolderRepository $folderRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Pour accéder à votre espace, vous devez vous connecter');
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

        $folder = $folderRepository->findOneBy(['name' => $folderName]);

        $filePath = $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $folder->getName();

        if (file_exists($filePath)) {
            return $this->render('home/viewFolder.html.twig', [
                'files' => $folder->getUploadedFiles(),
                'storageUsed' => $storageUsedGo,
                'storage' => $storage,
                'pourcentageStorageUsed' => $pourcentageStorageUsed,
                'folder' => $folder->getName()
            ]);
        }
        else
            $this->addFlash('warning', 'Le dossier que vous voulez ouvrir est introuvable');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/download/{fileName}', name: 'app_download')]
    public function download($fileName, Request $request): BinaryFileResponse
    {
        $user = $this->getUser();
        if (!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        $filePath =
            $request->query->get('folder')
                ?
                $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $request->query->get('folder') . '/' . $fileName
                :
                $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $fileName;

        if (file_exists($filePath))
        {
            $response = new BinaryFileResponse($filePath);
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName
            );

            return $response;
        }
        else
        {
            $this->addFlash('warning', 'Le fichier que vous voulez télécharger n\'existe pas');
            return $this->redirectToRoute('app_home');
        }
    }

    #[Route('/delete/{fileName}', name: 'app_delete')]
    public function delete(EntityManagerInterface $entityManager, string $fileName, UploadedFilesRepository $uploadedFilesRepository, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        $file = $uploadedFilesRepository->findOneBy(['name' => $fileName]);

        $filePath =
            $request->query->get('folder')
                ?
                $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $request->query->get('folder') . '/' . $file->getName()
                :
                $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $file->getName();

        if (file_exists($filePath) && is_file($filePath))
        {
            unlink($filePath);
            $user->setStorageUsed($user->getStorageUsed() - $file->getSize());

            $entityManager->remove($file);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Le fichier a été supprimé avec succès');

            if($request->query->get('folder'))
                return $this->redirectToRoute('app_viewFolder', ['folderName' => $request->query->get('folder')]);
            else
                return $this->redirectToRoute('app_home');
        }

        $this->addFlash('warning', 'Le fichier que vous voulez supprimer est introuvable');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/view/{fileName}', name: 'app_view')]
    public function view(string $fileName, UploadedFilesRepository $uploadedFilesRepository, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('warning', 'Pour accéder à votre espace, vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        $originalFileName = $uploadedFilesRepository->findOneBy(['name' => $fileName])->getOriginalName();

        $filePath =
            $request->query->get('folder')
                ?
                $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $request->query->get('folder') . '/' . $fileName
                :
                $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $fileName;

        if (file_exists($filePath)) {
            $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

            $allowedExtensions = ['txt', 'pdf', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'wmv', 'flv', 'avi', 'mkv'];

            if (in_array($fileExtension, $allowedExtensions))
            {
                if (in_array($fileExtension, ['txt']))
                {
                    return $this->render('home/viewTXT.html.twig', [
                        'content' => file_get_contents($filePath),
                        'originalFileName' => $originalFileName
                    ]);
                }
                elseif (in_array($fileExtension, ['pdf']))
                {
                    return $this->render('home/viewPDF.html.twig', [
                        'content' => base64_encode(file_get_contents($filePath)),
                        'originalFileName' => $originalFileName
                    ]);
                }
                elseif (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']))
                {
                    return $this->render('home/viewImage.html.twig', [
                        'content' => base64_encode(file_get_contents($filePath)),
                        'format' => $fileExtension,
                        'originalFileName' => $originalFileName
                    ]);
                }
                elseif (in_array($fileExtension, ['mp4', 'mov', 'wmv', 'flv', 'avi', 'mkv']))
                {
                    return $this->render('home/viewVideo.html.twig', [
                        'fileName' => $fileName,
                        'user' => $user,
                        'originalFileName' => $originalFileName,
                        'folder' => $request->query->get('folder')
                    ]);
                }
            }
            else
                $this->addFlash('warning', 'Le type de fichier n\'est pas autorisé');
        }
        else
            $this->addFlash('warning', 'Le fichier que vous voulez afficher est introuvable');

        return $this->redirectToRoute('app_home');
    }

    #[Route('/video/{username}/{fileName}', name: 'app_video')]
    public function video(string $username, string $fileName, Request $request): Response
    {
        $user = $this->getUser();

        if (!$user || $user->getUsername() !== $username) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $filePath =
            $request->query->get('folder')
                ?
                $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $request->query->get('folder') . '/' . $fileName
                :
                $this->getParameter('app.uploaddirectory') . $user->getUsername() . '/' . $fileName;

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw $this->createNotFoundException('La vidéo demandée est introuvable.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'video/mp4');

        return $response;
    }

    #[Route('/bienvenue', name: 'app_welcome')]
    public function welcome(): Response
    {
        $user = $this->getUser();
        if(!$user)
        {
            $this->addFlash('warning', 'Pour accéder à votre espace vous devez vous connecter');
            return $this->redirectToRoute('app_login');
        }

        $isValid = $user->getIsValid();
        if($isValid)
            return $this->redirectToRoute('app_home');

        return $this->render('home/welcome.html.twig', [

        ]);
    }
}
