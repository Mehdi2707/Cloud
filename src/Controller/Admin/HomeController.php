<?php

namespace App\Controller\Admin;

use App\Entity\Storage;
use App\Repository\StorageRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [

        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function users(UsersRepository $usersRepository): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $usersRepository->findAll()
        ]);
    }

    #[Route('/admin/storage', name: 'app_admin_storage')]
    public function storage(UsersRepository $usersRepository, StorageRepository $storageRepository): Response
    {
        $users = $usersRepository->findAll();
        $storage = $storageRepository->findAll();

        if(!$storage)
            $storage = null;
        else
            $storage = $storage[0]->getCapacity();

        $storageUsers = 0;
        $storageUsersUsed = 0;

        foreach($users as $user)
        {
            $storageUsers += $user->getStorage();
            $storageUsersUsed += $user->getStorageUsed();
        }

        $storageUsersUsedGo = round($storageUsersUsed / 1073741824, 2);

        if(!$storage)
        {
            $pourcentageStorageUsersUsed = 0;
            $pourcentageStorageUsers = 0;
        }
        else
        {
            $pourcentageStorageUsersUsed = round(($storageUsersUsedGo / $storage) * 100);
            $pourcentageStorageUsers = ($storageUsers / $storage) * 100;
        }

        return $this->render('admin/storage.html.twig', [
            'users' => $users,
            'storage' => $storage,
            'storageUsers' => $storageUsers,
            'storageUsersUsed' => $storageUsersUsedGo,
            'pourcentageStorageUsers' => $pourcentageStorageUsers,
            'pourcentageStorageUsersUsed' => $pourcentageStorageUsersUsed,
        ]);
    }

    #[Route('/admin/storage/modify', name: 'app_admin_edit_storage')]
    public function editStorage(Request $request, StorageRepository $storageRepository, EntityManagerInterface $entityManager): Response
    {
        $storageValue = $request->request->get('storageValue');
        $capacity = $storageRepository->findAll();

        if(!$capacity)
        {
            $storage = new Storage();
            $storage->setCapacity($storageValue);
            $entityManager->persist($storage);
            $entityManager->flush();

            return $this->json(['message' => 'La capacité de stockage à été ajouter avec succès']);
        }
        else
        {
            $capacity[0]->setCapacity($storageValue);
            $entityManager->persist($capacity[0]);
            $entityManager->flush();

            return $this->json(['message' => 'La capacité de stockage à été modifier avec succès']);
        }
    }

    #[Route('/admin/user/edit/{username}', name: 'app_admin_user_edit')]
    public function editUser($username, UsersRepository $usersRepository, StorageRepository $storageRepository): Response
    {
        $user = $usersRepository->findOneBy(['username' => $username]);
        $storage = $storageRepository->findAll()[0];

        return $this->render('admin/user.html.twig', [
            'user' => $user,
            'storage' => $storage
        ]);
    }

    #[Route('/admin/user/storage', name: 'app_admin_user_edit_storage')]
    public function editUserStorage(Request $request, UsersRepository $usersRepository, EntityManagerInterface $entityManager): Response
    {
        $storage = $request->request->get('storageValue');
        $username = $request->request->get('username');
        $user = $usersRepository->findOneBy(['username' => $username]);

        $user->setStorage($storage);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['message' => 'La capacité de stockage à été modifier avec succès']);
    }

    #[Route('/admin/access/on/{username}', name: 'app_admin_access_on')]
    public function accessOn($username, UsersRepository $usersRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $usersRepository->findOneBy(['username' => $username]);

        $user->setIsValid(true);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'L\'utilisateur peut dès à présent accéder à la plateforme !');
        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/access/off/{username}', name: 'app_admin_access_off')]
    public function accessOff($username, UsersRepository $usersRepository, EntityManagerInterface $entityManager): RedirectResponse
    {
        $user = $usersRepository->findOneBy(['username' => $username]);

        $user->setIsValid(false);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('danger', 'L\'utilisateur ne peut plus profiter des fonctionnalités de la plateforme !');
        return $this->redirectToRoute('app_admin');
    }
}