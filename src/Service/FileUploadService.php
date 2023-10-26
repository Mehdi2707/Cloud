<?php

namespace App\Service;

use App\Entity\UploadedFiles;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    private $slugger;
    private $entityManager;
    private $uploadDirectory;

    public function __construct(SluggerInterface $slugger, EntityManagerInterface $entityManager, string $uploadDirectory)
    {
        $this->slugger = $slugger;
        $this->entityManager = $entityManager;
        $this->uploadDirectory = $uploadDirectory;
    }

    public function uploadFile($file, Users $user): FileException|bool|\Exception|array
    {
        $filesystem = new Filesystem();
        $uploadedFile = new UploadedFiles();

        if ($file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

            try {
                if(!$filesystem->exists($this->uploadDirectory.$user->getUsername()))
                    $filesystem->mkdir($this->uploadDirectory.$user->getUsername());

            } catch (FileException $e) {
                return $e;
            }

            $file->move(
                $this->uploadDirectory.$user->getUsername(),
                $newFilename
            );

            $uploadedFile->setName($newFilename);
            $uploadedFile->setOriginalName($originalFilename);
            $uploadedFile->setUser($user);

            $this->entityManager->persist($uploadedFile);
            $this->entityManager->flush();
            return ['originalFilename' => $originalFilename, 'newFilename' => $newFilename];
        }

        return false;
    }
}