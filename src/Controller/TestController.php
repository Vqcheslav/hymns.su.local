<?php

namespace App\Controller;

use App\Service\BookService;
use App\Service\HymnService;
use App\Service\VerseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends Controller
{
    public const BOOK_ID_CUSTOM = 'custom';

    public const BOOK_ID_3400 = 'song-of-rebirth-3400';

    public const BOOK_ID_EHVDA = 'song-of-rebirth-ehvda';

    public const BOOK_ID_DEMYANSK = 'songbook-demyansk';

    private BookService $bookService;

    private HymnService $hymnsService;

    private VerseService $verseService;

    private EntityManagerInterface $entityManager;

    public function __construct(
        BookService $bookService,
        HymnService $hymnService,
        VerseService $verseService,
        EntityManagerInterface $entityManager
    )
    {
        $this->bookService = $bookService;
        $this->hymnsService = $hymnService;
        $this->verseService = $verseService;
        $this->entityManager = $entityManager;
    }

    #[Route("/test/get-json/{bookId}/{filename}/{startHymnNumber}", name: "test.getJson", methods: ["GET"])]
    public function getJson(string $bookId, string $filename, int $startHymnNumber = null): Response
    {
        $book = $this->bookService->getBookByBookId($bookId);

        if ($book === null) {
            return $this->jsonResponse(false, $bookId, 'Book not found', Response::HTTP_NOT_FOUND);
        }

        $hymns = file_get_contents(__DIR__ . sprintf('/../../public/txt/%s.txt', $filename));
        $hymns = $this->hymnsService->convertTxtPart($hymns, $startHymnNumber);
        file_put_contents(
            __DIR__ . sprintf('/../../public/json/%s.json', $filename),
            $this->bookService->jsonEncode($hymns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)->getData()
        );

        return $this->jsonResponse(true, $hymns, sprintf('Songs of %s retrieved', $bookId), Response::HTTP_OK);
    }

    #[Route("/test/fill-table-by-raw-data/{bookId}/{filename}", name: "test.fillTableByRawData", methods: ["GET"])]
    public function fillTableByRawDataJson(string $bookId, string $filename): Response
    {
        $book = $this->bookService->getBookByBookId($bookId);

        if ($book === null) {
            return $this->jsonResponse(false, $bookId, 'Book not found', Response::HTTP_NOT_FOUND);
        }

        $filenameOfCategories = __DIR__ . sprintf('/../../public/json/categories_%s.json', $filename);

        if (! file_exists($filenameOfCategories)) {
            $filenameOfCategories = __DIR__ . '/../../public/json/categories_all.json';
        }

        $hymns = $this->bookService
            ->jsonDecode(file_get_contents(__DIR__ . sprintf('/../../public/json/%s.json', $filename)))
            ->getData();
        $categories = $this->bookService
            ->jsonDecode(file_get_contents($filenameOfCategories))
            ->getData();
        $hymnsResult = $verseResult = [];
        $lastHymnNumber = $this->hymnsService->getMaxHymnNumber($bookId);

        foreach ($hymns as $data) {
            if ($data['number'] <= $lastHymnNumber || ($lastHymnNumber > 0 && $data['number'] > $lastHymnNumber + 200)) {
                continue;
            }

            $this->entityManager->beginTransaction();

            $hymn = $this->hymnsService->parseAndCreateHymn(
                $book, $categories, $data['number'], $data['name'], '', false
            );
            $lastHymnNumber = $data['number'];
            $hymnsResult[] = $hymn->getHymnId();
            $verseResultDto = $this->verseService->parseAndCreateVersesForHymn(
                $hymn, $data['lyrics'], false
            );

            if ($verseResultDto->hasErrors()) {
                return $this->jsonResponseFromDto($verseResultDto);
            }

            $verseResult[] = $verseResultDto->getData();

            try {
                $this->entityManager->commit();
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                //$this->entityManager->rollback();

                return $this->jsonResponse(false, $e, $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return $this->jsonResponse(true, [
            'last_hymn_number' => $this->hymnsService->getMaxHymnNumber($bookId),
            'book'             => $bookId,
            'hymn_result'      => $hymnsResult,
            'verse_result'     => $verseResult,
        ], 'Successfully');
    }
}
