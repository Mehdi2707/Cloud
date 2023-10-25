<?php

namespace App\MessageHandler;

use App\Entity\UploadedFiles;
use App\Entity\Users;
use App\Message\FileUploadMessage;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final class FileUploadHandler implements MessageHandlerInterface
{
    private $entityManager;
    private $fileUploadService;

    public function __construct(EntityManagerInterface $entityManager, FileUploadService $fileUploadService)
    {
        // Configuration du service de traitement des fichiers (par exemple, enregistrement en base de données).
        $this->entityManager = $entityManager;
        $this->fileUploadService = $fileUploadService;
    }

    public function __invoke(FileUploadMessage $message)
    {
        $file = $this->entityManager->find(UploadedFiles::class, $message->getFileName());
        $user = $this->entityManager->find(Users::class, $message->getUserId());

        if($user !== null && $file !== null)
        {
            $this->fileUploadService->uploadFile($file, $user);
        }
//        $slugger = SluggerInterface::class;
//        $filesystem = Filesystem::class;
//        $uploadedFile = new UploadedFiles();
//        $entityManager = EntityManagerInterface::class;
//
//        // Traitez le fichier ici (par exemple, enregistrez-le en base de données).
//        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
//        // this is needed to safely include the file name as part of the URL
//        $safeFilename = $this->slugger->slug($originalFilename);
//        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
//
//        // Move the file to the directory where brochures are stored
//        try {
//            if(!$this->filesystem->exists('/home/mehdi/'.$user->getUsername()))
//                $this->filesystem->mkdir('/home/mehdi/'.$user->getUsername());
//
//            $file->move(
//                '/home/mehdi/'.$user->getUsername(),
//                $newFilename
//            );
//        } catch (FileException $e) {
//            // ... handle exception if something happens during file upload
//        }
//
//        // updates the 'brochureFilename' property to store the PDF file name
//        // instead of its contents
//        $uploadedFile->setName($newFilename);
//        $uploadedFile->setOriginalName($originalFilename);
//        $uploadedFile->setUser($user);
//
//        $this->entityManager->persist($uploadedFile);
//        $this->entityManager->flush();
    }
}