<?php

namespace App\Service;

use App\Dto\ResultDto;
use App\Entity\Book;
use App\Entity\Hymn;
use App\Normalizer\HymnNormalizer;
use App\Normalizer\VerseNormalizer;
use App\Repository\HymnRepository;
use App\Repository\VerseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Throwable;

class HymnService extends Service
{
    public const int SEARCH_RESULTS_LIMIT = 30;

    public const int HYMNS_WITH_VERSES_LIMIT = 1200;

    public const string CATEGORY_HYMNS_IN_ENGLISH = 'Hymns in English';

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
        private readonly SluggerInterface $slugger,
        private readonly HymnRepository $hymnRepository,
        private readonly VerseRepository $verseRepository,
        private readonly HymnNormalizer $hymnNormalizer,
        private readonly VerseNormalizer $verseNormalizer,
        private readonly BookService $bookService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function replaceInvalidSymbols(string $text, string $category = ''): string
    {
        if ($category === self::CATEGORY_HYMNS_IN_ENGLISH) {
            return $text;
        }

        return str_replace(array_keys(self::REPLACED_SYMBOLS), array_values(self::REPLACED_SYMBOLS), $text);
    }

    public function getHymnsByBookId(string $bookId, int $startNumber, int $endNumber): ResultDto
    {
        try {
            $bookId = $this->bookService->getCorrectBookId($bookId, $endNumber);
            $hymns = $this->hymnRepository->getHymnsByBookId($bookId, $startNumber, $endNumber);
            $hymns = $this->hymnNormalizer->normalizeArray($hymns);
        } catch (Throwable) {
            return $this->makeResultDto(false, [], 'Cannot retrieve hymns by book id: ' . $bookId);
        }

        return $this->makeResultDto(true, $hymns, 'Successfully retrieved hymns');
    }

    public function getHymnsByCategory(string $category, int $offset, int $limit): ResultDto
    {
        try {
            $category = trim($category);
            $hymns = $this->hymnRepository->getHymnsByCategory($category, $offset, $limit);
            $hymns = $this->hymnNormalizer->normalizeArrayWithVerses($hymns);
        } catch (Throwable) {
            return $this->makeResultDto(false, [], 'Cannot retrieve hymns by category: ' . $category);
        }

        return $this->makeResultDto(true, $hymns, 'Successfully retrieved hymns');
    }

    public function getHymnsWithVerses(string $bookId, int $startNumber, int $endNumber): ResultDto
    {
        try {
            $bookId = $this->bookService->getCorrectBookId($bookId, $endNumber);
            $hymns = $this->hymnRepository
                ->getHymnsWithVerses($bookId, $startNumber, $endNumber, self::HYMNS_WITH_VERSES_LIMIT);
            $hymns = $this->hymnNormalizer->normalizeArrayWithVerses($hymns);
        } catch (Throwable) {
            return $this->makeResultDto(false, [], 'Cannot retrieve hymns with verses by book id: ' . $bookId);
        }

        return $this->makeResultDto(true, $hymns, 'Successfully retrieved hymns');
    }

    public function getHymnByHymnId(string $hymnId): ResultDto
    {
        try {
            $hymn = $this->hymnRepository->getHymnByHymnId($hymnId);
            $hymn = $this->hymnNormalizer->normalizeWithVerses($hymn);
        } catch (Throwable) {
            return $this->makeResultDto(false, [], 'Not found');
        }

        return $this->makeResultDto(true, $hymn, 'Successfully retrieved hymn');
    }

    public function prepareSearchRequest(string $search): string
    {
        return trim(preg_replace('/\p{P}/u', ' ', $search));
    }

    public function getSearchExpression(string $search): string
    {
        $exploded = explode(' ', $search);
        $exploded = array_filter($exploded);

        return sprintf('%%%s%%', implode('%', $exploded));
    }

    public function searchHymns(string $search, int $limit = self::SEARCH_RESULTS_LIMIT): ResultDto
    {
        $search = $this->prepareSearchRequest($search);

        try {
            if (is_numeric($search)) {
                $hymns = $this->hymnRepository->searchHymnsByNumber($search);
                $hymns = $this->hymnNormalizer->normalizeArrayWithFirstVerse($hymns);
            } else {
                $searchExpression = $this->getSearchExpression($search);
                $halfLimit = (int) ($limit / 2);

                $hymnsByTitle = $this->hymnRepository->searchHymnsByTitle($searchExpression, $halfLimit);
                $hymnsByTitle = $this->hymnNormalizer->normalizeArrayWithFirstVerse($hymnsByTitle);

                $hymnsByLyrics = $this->verseRepository->searchVerses($searchExpression, $halfLimit);
                $hymnsByLyrics = $this->verseNormalizer->normalizeArrayWithHymns($hymnsByLyrics);

                $hymns = $this->getUniqueHymnsFromResults($hymnsByTitle, $hymnsByLyrics);
            }

            $this->entityManager->clear();
        } catch (Throwable) {
            return $this->makeResultDto(false, [], 'Cannot find hymns by: ' . $search);
        }

        return $this->makeResultDto(true, $hymns, 'Successfully retrieved hymns');
    }

    public function getUniqueHymnsFromResults(array ...$hymns): array
    {
        $result = [];

        foreach ($hymns as $hymnList) {
            foreach ($hymnList as $hymn) {
                if (isset($result[$hymn['hymn_id']])) {
                    continue;
                }

                $result[$hymn['hymn_id']] = $hymn;
            }
        }

        return array_values($result);
    }

    public function getHymnCategories(): ResultDto
    {
        try {
            $categories = $this->hymnRepository->getHymnCategories();
        } catch (Throwable $e) {
            return $this->makeResultDto(false, $e, 'Not found');
        }

        return $this->makeResultDto(true, $categories, 'Successfully retrieved hymn categories');
    }

    public function getUpdatedHymns(string $afterDate): ResultDto
    {
        $timestamp = $this->getTimestamp($afterDate);
        $afterDate = $this->dateTimeFormat($timestamp);

        try {
            $result = $this->hymnRepository->getUpdatedHymns($afterDate);
        } catch (Throwable) {
            return $this->makeResultDto(false, [], 'Cannot retrieve updated hymns after: ' . $afterDate);
        }

        return $this->makeResultDto(true, $result, 'Successfully retrieved updated hymns');
    }

    public function parseAndCreateHymn(
        Book $book,
        array $categories,
        int $number,
        string $title,
        string $tone,
        bool $flush = true,
    ): Hymn {
        $categoryName = '';

        foreach ($categories as $category) {
            if ($number >= $category['min'] && $number <= $category['max']) {
                $categoryName = $category['title'];
            }
        }

        return $this->createHymn($book, $number, $title, $categoryName, $tone, $flush);
    }

    public function generateHymnId(int $number, string $title): string
    {
        $uniqId = uniqid('', true) . uniqid('', true);
        $uniqId = str_replace(['.', '666'], ['', '555'], $uniqId);
        $string = sprintf('%s-%s', $number, mb_substr($title, 0, 35));

        return mb_strtolower(mb_substr($this->slugger->slug($string) . $uniqId, 0, 50));
    }

    public function createHymn(
        Book $book,
        int $number,
        string $title,
        string $category,
        string $tone,
        bool $flush = true,
        ?string $hymnId = null,
    ): Hymn {
        if ($hymnId === null) {
            $hymnId = $this->generateHymnId($number, $title);
        }

        $hymn = new Hymn();
        $hymn
            ->setHymnId($hymnId)
            ->setBook($book)
            ->setNumber($number)
            ->setTitle(trim($title))
            ->setCategory(trim($category))
            ->setTone(trim($tone));
        $this->hymnRepository->add($hymn, $flush);

        return $hymn;
    }

    public function getMaxHymnNumber(string $bookId): int
    {
        try {
            return (int) $this->hymnRepository->getMaxHymnNumber($bookId);
        } catch (Throwable) {
            return 0;
        }
    }

    public function convertTxtPart(string $hymns, ?int $startHymnNumber = null): array
    {
        $result = [];
        $hymnList = str_replace("\r\n\r\n", "\n\n", $hymns);
        $hymnList = explode("\n\n", $hymnList);

        foreach ($hymnList as $hymn) {
            [$number] = explode(' ', $hymn);
            $lyrics = trim(str_replace([$number . ' ', "\r\n"], ['', "\n"], $hymn));
            preg_match("/(\p{L}+[ «\-…,.?!:;–()']*){6}/mu", $lyrics, $matches);
            $name = trim(str_replace(
                [
                    '"', '«', '»', '(', ')', ':,:', ':', ';', '–',
                    'Припев', 'Хор.', 'Хор', 'Чол.', 'Жін.', 'Діск', 'Діс.', 'Діс', 'Приспів', 'Бас.', 'Бас',
                    'припев', 'хор.', 'хор', 'чол.', 'жін.', 'діск', 'діс.', 'діс', 'приспів', 'бас.', 'бас',
                    '  ',
                ],
                [
                    '', '', '', '', '', '', ',', ',', ' ',
                    '', '', '', '', '', '', '', '', '', '', '',
                    '', '', '', '', '', '', '', '', '', '', '',
                    ' ',
                ],
                $matches[0]
            ), "\ \n\r\t\v\0,.!'?-");

            $result[] = [
                'number' => $startHymnNumber === null ? $number : $startHymnNumber++,
                'name'   => $name,
                'lyrics' => $lyrics,
            ];
        }

        return $result;
    }
}
