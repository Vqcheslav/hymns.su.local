<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HymnsController extends Controller
{
    #[Route('/', name: 'homepage', methods: ['GET', 'HEAD'])]
    public function homepage(Request $request): Response
    {
        $url = $request->getUri();

        if (str_contains($url, 'hymns.su')) {
            return $this->redirect(str_replace('hymns.su', 'psalma.name', $url), Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->render('hymns/main.html.twig');
    }
}
