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

    public function uploadFile($file, Users $user, $fileSize): FileException|bool|\Exception|array
    {
        $filesystem = new Filesystem();
        $uploadedFile = new UploadedFiles();
        $storage = $user->getStorage();
        $storageUsed = $user->getStorageUsed();
        $storageUsedGo = round($storageUsed / 1073741824, 2);
        $fileSizeMax = ($storage - $storageUsedGo);

        if ($file)
        {
            $fileSizeGo = round($fileSize / 1073741824, 2);

            if($fileSizeGo > $fileSizeMax)
                return false;

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

            if(!$filesystem->exists($this->uploadDirectory))
                return false;

            try
            {
                if(!$filesystem->exists($this->uploadDirectory.$user->getUsername()))
                    $filesystem->mkdir($this->uploadDirectory.$user->getUsername());
            }
            catch (FileException $e)
            {
                return $e;
            }

            $file->move(
                $this->uploadDirectory.$user->getUsername(),
                $newFilename
            );

            $uploadedFile->setName($newFilename);
            $uploadedFile->setOriginalName($originalFilename);
            $uploadedFile->setUser($user);
            $uploadedFile->setSize($fileSize);

            $this->entityManager->persist($uploadedFile);
            $this->entityManager->flush();
            return ['originalFilename' => $originalFilename, 'newFilename' => $newFilename];
        }

        return false;
    }
}