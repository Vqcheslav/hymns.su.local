<?php

namespace App\Service;

use App\Dto\ResultDto;
use App\Entity\Book;
use App\Normalizer\BookNormalizer;
use App\Repository\BookRepository;
use Throwable;

class BookService extends Service
{
    private BookRepository $bookRepository;

    private BookNormalizer $bookNormalizer;

    public function __construct(BookRepository $bookRepository, BookNormalizer $bookNormalizer)
    {
        $this->bookRepository = $bookRepository;
        $this->bookNormalizer = $bookNormalizer;
    }

    public function getBookByBookId(string $bookId): ?Book
    {
        return $this->bookRepository->find($bookId);
    }

    public function getBooks(): ResultDto
    {
        try {
            $books = $this->bookRepository->getAllBooks();
            $books = $this->bookNormalizer->normalizeArray($books);
        } catch (Throwable) {
            return $this->makeResultDto(false, [], 'Cannot retrieve books', 500);
        }

        return $this->makeResultDto(true, $books, 'Successfully retrieved books');
    }

    public function getCorrectBookId(string $bookId, int $endNumber): string
    {
        if ($bookId === 'song-of-rebirth-ehvda' && $endNumber < 2401) {
            return 'song-of-rebirth-3400';
        }

        $booksResultDto = $this->getBooks();
        $bookIds = array_keys($booksResultDto->getData());

        return in_array($bookId, $bookIds, true) ? $bookId : 'song-of-rebirth-3400';
    }
}
