<?php

namespace App\Service;

use App\Entity\Folder;
use App\Entity\Users;
use App\Repository\FolderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FolderService
{
    private $entityManager;
    private $uploadDirectory;
    private $folderRepository;

    public function __construct(EntityManagerInterface $entityManager, string $uploadDirectory, FolderRepository $folderRepository)
    {
        $this->entityManager = $entityManager;
        $this->uploadDirectory = $uploadDirectory;
        $this->folderRepository = $folderRepository;
    }

    public function createFolder($folderName, Users $user): FileException|bool|\Exception|array
    {
        $filesystem = new Filesystem();
        $folder = new Folder();

        if ($folderName)
        {
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

            if(!$filesystem->exists($this->uploadDirectory.$user->getUsername().DIRECTORY_SEPARATOR.$folderName))
                $filesystem->mkdir($this->uploadDirectory.$user->getUsername().DIRECTORY_SEPARATOR.$folderName);
            else
                return false;

            $folder->setName($folderName);
            $folder->setUser($user);

            $this->entityManager->persist($folder);
            $this->entityManager->flush();
            return ['folderName' => $folderName];
        }

        return false;
    }

    public function updateFolder($oldFolderName, $newFolderName, Users $user): FileException|bool|\Exception|array
    {
        $filesystem = new Filesystem();
        $folder = $this->folderRepository->findOneBy(["name" => $oldFolderName]);

        if ($newFolderName && $folder)
        {
            if(!$filesystem->exists($this->uploadDirectory))
                return false;

            if(!$filesystem->exists($this->uploadDirectory.$user->getUsername().DIRECTORY_SEPARATOR.$newFolderName))
                $filesystem->rename(
                    $this->uploadDirectory.$user->getUsername().DIRECTORY_SEPARATOR.$oldFolderName,
                    $this->uploadDirectory.$user->getUsername().DIRECTORY_SEPARATOR.$newFolderName
                );
            else
                return false;

            $folder->setName($newFolderName);

            $this->entityManager->persist($folder);
            $this->entityManager->flush();
            return ['newFolderName' => $newFolderName];
        }

        return false;
    }
}