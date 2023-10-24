<?php

namespace App\Controller\Admin;

use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(UsersRepository $usersRepository): Response
    {
        return $this->render('admin/index.html.twig', [
            'users' => $usersRepository->findAll()
        ]);
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