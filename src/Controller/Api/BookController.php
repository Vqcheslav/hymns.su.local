<?php

namespace App\Controller\Api;

use App\Controller\Controller;
use App\Service\BookService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends Controller
{
    private BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    #[Route("/api/v1/books", name: "books.getBooks", methods: ["GET"])]
    public function getBooks(): Response
    {
        $resultDto = $this->bookService->getBooks();

        return $this->jsonResponseFromDto($resultDto);
    }
}
