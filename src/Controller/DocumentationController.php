<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DocumentationController extends Controller
{
    #[Route("/api/docs", name: "api.docs", methods: ["GET", "HEAD"])]
    public function index(): Response
    {
        return $this->render('documentation/documentation.html.twig', [
            'phpVersion' => 'PHP: ' . PHP_VERSION
        ]);
    }
}
