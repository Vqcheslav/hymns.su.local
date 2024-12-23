<?php

namespace App\Controller;

use App\Service\BookService;
use App\Service\HymnService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HymnsController extends Controller
{
    public const DEFAULT_BOOK = 'song-of-rebirth-ehvda';

    public const RESULTS_PER_PAGE = 100;

    private HymnService $hymnService;

    private BookService $bookService;

    public function __construct(HymnService $hymnService, BookService $bookService)
    {
        $this->hymnService = $hymnService;
        $this->bookService = $bookService;
    }

    #[Route("/{bookId}/{page<\d+>}", name: "hymns", methods: ["GET", "HEAD"])]
    public function index(string $bookId = self::DEFAULT_BOOK, int $page = 1): Response
    {
        $booksResultDto = $this->bookService->getBooks();
        $hymnsResultDto = $this->hymnService->getHymnsByBookId(
            $bookId,
            $page * self::RESULTS_PER_PAGE - self::RESULTS_PER_PAGE,
            $page * self::RESULTS_PER_PAGE,
        );
        $books = $booksResultDto->getData();
        $totalSongs = $books[$bookId]['total_songs'] ?? 100;

        return $this->render('hymns/hymns.html.twig', [
            'bookId'         => $bookId,
            'page'           => $page,
            'resultsPerPage' => self::RESULTS_PER_PAGE,
            'totalSongs'     => $totalSongs,
            'books'          => $booksResultDto->getData(),
            'hymns'          => $hymnsResultDto->getData(),
        ]);
    }
}
