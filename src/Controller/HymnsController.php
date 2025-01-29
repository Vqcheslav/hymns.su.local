<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HymnsController extends Controller
{
    #[Route('/', name: 'homepage', methods: ['GET', 'HEAD'])]
    public function homepage(): Response
    {
        return $this->render('hymns/main.html.twig');
    }

    #[Route('/{bookId}/{page<\d+>}', name: 'hymns', methods: ['GET', 'HEAD'])]
    public function redirectToHomepage(string $bookId, int $page = 1): Response
    {
        return $this->redirect($this->generateUrl('homepage'));
    }

    #[Route('/hymns/book/{bookId}/{page<\d+>}', name: 'hymns.redirect', methods: ['GET', 'HEAD'])]
    public function redirectToIndex(string $bookId = '', int $page = 1): RedirectResponse
    {
        return $this->redirect($this->generateUrl('homepage'));
    }
}
