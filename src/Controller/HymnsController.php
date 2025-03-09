<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HymnsController extends Controller
{
    #[Route('/', name: 'homepage', methods: ['GET', 'HEAD'])]
    public function homepage(): Response
    {
        return $this->render('hymns/main.html.twig');
    }
}
