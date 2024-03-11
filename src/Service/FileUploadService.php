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

    public function uploadFile($file, Users $user, $fileSize, $folder): FileException|bool|\Exception|array
    {
        $filesystem = new Filesystem();
        $uploadedFile = new UploadedFiles();
        $storage = $user->getStorage();
        $storageOct = round($storage * 1073741824, 2);
        $storageUsed = $user->getStorageUsed();
        $fileSizeMax = ($storageOct - $storageUsed);

        if ($file)
        {
            if($fileSize > $fileSizeMax)
                return false;

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->getClientOriginalExtension();
            $extension = $file->getClientOriginalExtension();

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

            if(!$folder)
                $file->move(
                    $this->uploadDirectory.$user->getUsername(),
                    $newFilename
                );
            else
                $file->move(
                    $this->uploadDirectory.$user->getUsername().DIRECTORY_SEPARATOR.$folder->getName(),
                    $newFilename
                );

            $formatsVideoToChange = [ 'mov', 'wmv', 'flv', 'avi', 'mkv' ];

            if(in_array($extension, $formatsVideoToChange))
            {
                $folder = $folder->getName() ? $this->uploadDirectory.$user->getUsername().DIRECTORY_SEPARATOR.$folder->getName() : $this->uploadDirectory.$user->getUsername();
                $newVideoName = pathinfo($newFilename, PATHINFO_FILENAME).'.mp4';

                exec('/usr/bin/ffmpeg -y -i '.$folder.DIRECTORY_SEPARATOR.$newFilename.' -c:v libx264 -c:a aac -pix_fmt yuv420p -movflags faststart -hide_banner '.$folder.DIRECTORY_SEPARATOR.$newVideoName.' 2>&1', $out, $res);

                if($res == 0)
                {
                    unlink($folder . DIRECTORY_SEPARATOR . $newFilename);
                    $newFilename = $newVideoName;
                    $fileSize = filesize($folder.DIRECTORY_SEPARATOR.$newVideoName);
                }
                else
                {
                    return false;
                }
            }

            $uploadedFile->setName($newFilename);
            $uploadedFile->setOriginalName($originalFilename);
            $uploadedFile->setUser($user);
            $uploadedFile->setSize($fileSize);
            $uploadedFile->setFolder($folder);

            $this->entityManager->persist($uploadedFile);
            $this->entityManager->flush();
            return ['originalFilename' => $originalFilename, 'newFilename' => $newFilename];
        }

        return false;
    }
}