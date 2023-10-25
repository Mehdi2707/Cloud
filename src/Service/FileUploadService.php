<?php

namespace App\Service;

use App\Entity\UploadedFiles;
use App\Entity\Users;
use App\Message\FileUploadMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    private $slugger;
    private $entityManager;

    public function __construct(SluggerInterface $slugger, EntityManagerInterface $entityManager)
    {
        $this->slugger = $slugger;
        $this->entityManager = $entityManager;
    }

    public function uploadFile($file, Users $user)
    {
        try {
//            move_uploaded_file($file, '/home/mehdi/' . $user->getUsername() . '/' . $file);
            $file->move(
                '/home/mehdi/'.$user->getUsername(),
                $file
            );
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }
    }
}