<?php

namespace App\Controller;

use App\Entity\Verse;
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

    public const int LIMIT = 30;

    public const array REPLACED_SYMBOLS = [
        'e' => 'е',
        'E' => 'Е',
        't' => 'т',
        'T' => 'Т',
        'i' => 'і',
        'I' => 'І',
        'o' => 'о',
        'O' => 'О',
        'p' => 'р',
        'P' => 'Р',
        'a' => 'а',
        'A' => 'А',
        'x' => 'х',
        'X' => 'Х',
        'c' => 'с',
        'C' => 'С',
    ];

    public function __construct(
        private readonly BookService $bookService,
        private readonly HymnService $hymnService,
        private readonly VerseService $verseService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route("/test/fix/russian/{index}", name: "test.fix_russian_songs", methods: ["GET"])]
    public function fixRussianVerses(int $index = 0): Response
    {
        $invalidSymbols = array_keys(self::REPLACED_SYMBOLS);
        $validSymbols = array_values(self::REPLACED_SYMBOLS);

        $searchInvalidSymbol = $invalidSymbols[$index] ?? $invalidSymbols[0];
        $verses = $this->entityManager
            ->getRepository(Verse::class)
            ->createQueryBuilder('v')
            ->select('v', 'h')
            ->join('v.hymn', 'h')
            ->andWhere('v.lyrics LIKE :search')
            ->andWhere('h.category <> :category')
            ->orderBy('h.number', 'ASC')
            ->setMaxResults(self::LIMIT)
            ->setParameter('search', $this->hymnService->getSearchExpression($searchInvalidSymbol))
            ->setParameter('category', 'Hymns in English')
            ->getQuery()
            ->getResult();
        $fixedVerseIds = [];

        foreach ($verses as $verse) {
            /* @var Verse $verse */
            $verse->setLyrics(str_replace($invalidSymbols, $validSymbols, $verse->getLyrics()));

            $fixedVerseIds[] = $verse->getVerseId();
            $this->entityManager->persist($verse);
        }

        $this->entityManager->flush();
        $fixedVerseIds = array_values(array_unique($fixedVerseIds));

        return $this->jsonResponse(true, $fixedVerseIds, 'Successfully fixed invalid symbol (and other): ' . $searchInvalidSymbol);
    }

    #[Route("/test/get-json/{bookId}/{filename}/{startHymnNumber}", name: "test.getJson", methods: ["GET"])]
    public function getJson(string $bookId, string $filename, int $startHymnNumber = null): Response
    {
        $book = $this->bookService->getBookByBookId($bookId);

        if ($book === null) {
            return $this->jsonResponse(false, $bookId, 'Book not found', Response::HTTP_NOT_FOUND);
        }

        $hymns = file_get_contents(__DIR__ . sprintf('/../../public_html/txt/%s.txt', $filename));
        $hymns = $this->hymnService->convertTxtPart($hymns, $startHymnNumber);
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
        $lastHymnNumber = $this->hymnService->getMaxHymnNumber($bookId);

        foreach ($hymns as $data) {
            if ($data['number'] <= $lastHymnNumber
                || ($lastHymnNumber > 0
                    && $data['number'] > $lastHymnNumber + 200)
            ) {
                continue;
            }

            $this->entityManager->beginTransaction();

            $hymn = $this->hymnService->parseAndCreateHymn(
                $book,
                $categories,
                $data['number'],
                $data['name'],
                '',
                false,
            );
            $lastHymnNumber = $data['number'];
            $hymnsResult[] = $hymn->getHymnId();
            $verseResultDto = $this->verseService->parseAndCreateVersesForHymn(
                $hymn,
                $data['lyrics'],
                false,
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
            'last_hymn_number' => $this->hymnService->getMaxHymnNumber($bookId),
            'book'             => $bookId,
            'hymn_result'      => $hymnsResult,
            'verse_result'     => $verseResult,
        ], 'Successfully');
    }
}
