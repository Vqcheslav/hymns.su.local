<?php

namespace App\Controller;

use App\Entity\Hymn;
use App\Service\BookService;
use App\Service\HymnService;
use App\Service\VerseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class TestController extends Controller
{
    public const string BOOK_ID_EHVDA = 'song-of-rebirth-ehvda';

    private BookService $bookService;

    private HymnService $hymnsService;

    private VerseService $verseService;

    private EntityManagerInterface $entityManager;

    public function __construct(
        BookService $bookService,
        HymnService $hymnService,
        VerseService $verseService,
        EntityManagerInterface $entityManager,
    ) {
        $this->bookService = $bookService;
        $this->hymnsService = $hymnService;
        $this->verseService = $verseService;
        $this->entityManager = $entityManager;
    }

    #[Route("/test/fix/ukrainian/{startNumber}", name: "test.fix_ukrainian_songs", methods: ["GET"])]
    public function fixUkrainianVerses(int $startNumber = 2400): Response
    {
        $endNumber = $startNumber + 100;
        $hymns = $this->entityManager
            ->getRepository(Hymn::class)
            ->getHymnsWithVerses(self::BOOK_ID_EHVDA, $startNumber, $endNumber);
        $fixedHymnIds = [];

        foreach ($hymns as $hymn) {
            foreach ($hymn->getVerses() as $verse) {
                if (str_contains($verse->getLyrics(), 'i') || str_contains($verse->getLyrics(), 'I')) {
                    $fixedHymnIds[] = $hymn->getHymnId();
                    $verse->setLyrics(str_replace('i', 'і', $verse->getLyrics()));
                    $verse->setLyrics(str_replace('I', 'І', $verse->getLyrics()));
                }

                $this->entityManager->persist($verse);
            }

            $this->entityManager->flush();
        }

        $fixedHymnIds = array_values(array_unique($fixedHymnIds));

        return $this->jsonResponse(true, $fixedHymnIds, 'End Number: ' . $endNumber);
    }

    #[Route("/test/get-json/{bookId}/{filename}/{startHymnNumber}", name: "test.getJson", methods: ["GET"])]
    public function getJson(string $bookId, string $filename, int $startHymnNumber = null): Response
    {
        $book = $this->bookService->getBookByBookId($bookId);

        if ($book === null) {
            return $this->jsonResponse(false, $bookId, 'Book not found', Response::HTTP_NOT_FOUND);
        }

        $hymns = file_get_contents(__DIR__ . sprintf('/../../public_html/txt/%s.txt', $filename));
        $hymns = $this->hymnsService->convertTxtPart($hymns, $startHymnNumber);
        file_put_contents(
            __DIR__ . sprintf('/../../public_html/json/%s.json', $filename),
            $this->bookService->jsonEncode($hymns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)->getData(),
        );

        return $this->jsonResponse(true, $hymns, sprintf('Songs of %s retrieved', $bookId));
    }

    #[Route("/test/fill-table-by-json/{bookId}/{filename}", name: "test.fillTableByRawData", methods: ["GET"])]
    public function fillTableByRawDataJson(string $bookId, string $filename): Response
    {
        $book = $this->bookService->getBookByBookId($bookId);

        if ($book === null) {
            return $this->jsonResponse(false, $bookId, 'Book not found', Response::HTTP_NOT_FOUND);
        }

        $filenameOfCategories = __DIR__ . sprintf('/../../public_html/json/categories_%s.json', $filename);

        if ( ! file_exists($filenameOfCategories)) {
            $filenameOfCategories = __DIR__ . '/../../public_html/json/categories_all.json';
        }

        $hymns = $this->bookService
            ->jsonDecode(file_get_contents(__DIR__ . sprintf('/../../public_html/json/%s.json', $filename)))
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
            } catch (Throwable $e) {
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
