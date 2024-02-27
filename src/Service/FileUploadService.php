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
        $storageOct = round($storage * 1073741824, 2);
        $storageUsed = $user->getStorageUsed();
        $fileSizeMax = ($storageOct - $storageUsed);

        if ($file)
        {
            if($fileSize > $fileSizeMax)
                return false;

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();
            $extension = $file->guessExtension();

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

            $formatsVideoToChange = [ 'mov', 'wmv', 'flv', 'avi', 'mkv' ];

            if(in_array($extension, $formatsVideoToChange))
            {
                $folder = $this->uploadDirectory.$user->getUsername();
                $newVideoName = pathinfo($newFilename, PATHINFO_FILENAME).'.mp4';

                exec('/usr/bin/ffmpeg -y -i '.$folder.DIRECTORY_SEPARATOR.$newFilename.' -c:v libx264 -c:a aac -pix_fmt yuv420p -movflags faststart -hide_banner '.$folder.DIRECTORY_SEPARATOR.$newVideoName.' 2>&1', $out, $res);

                if($res == 0)
                {
                    unlink($folder . DIRECTORY_SEPARATOR . $newFilename);
                    $newFilename = $newVideoName;
                }
                else
                {
                    error_log(var_export($out, true));
                    error_log(var_export($res, true));

                    return false;
                }
            }

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