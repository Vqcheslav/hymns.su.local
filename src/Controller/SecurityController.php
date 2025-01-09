<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route("/admin/login", name: "app_login", methods: ["GET", "HEAD", "POST"])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@EasyAdmin/page/login.html.twig', [
            'error'                => $error,
            'last_username'        => $lastUsername,
            'translation_domain'   => 'login',
            'favicon_path'         => '/img/hymns/favicon.png',
            'page_title'           => 'Гимны: Вход',
            'csrf_token_intention' => 'authenticate',
            'target_path'          => $this->generateUrl('admin'),
            'username_label'       => 'Email',
            'password_label'       => 'Password',
            'sign_in_label'        => 'Log in',
            'remember_me_enabled'  => true,
            'remember_me_checked'  => true,
            'remember_me_label'    => 'Remember me',
        ]);
    }
}
