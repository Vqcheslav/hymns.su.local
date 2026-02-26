<?php

namespace App\Controller\Api;

use App\Controller\Controller;
use App\Service\BookService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
    ) {}

    #[Route("/api/v1/books", name: "books.getBooks", methods: ["GET"])]
    public function getBooks(): Response
    {
        $resultDto = $this->bookService->getBooks();

        return $this->jsonResponseFromDto($resultDto);
    }
}
