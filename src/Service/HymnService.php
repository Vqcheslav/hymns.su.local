<?php

namespace App\Service;

use App\Dto\ResultDto;
use App\Entity\Book;
use App\Entity\Hymn;
use App\Normalizer\HymnNormalizer;
use App\Normalizer\VerseNormalizer;
use App\Repository\HymnRepository;
use App\Repository\VerseRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Throwable;

class HymnService extends Service
{
    public const int SEARCH_RESULTS_LIMIT = 30;

    public const int HYMNS_WITH_VERSES_LIMIT = 1200;

    private SluggerInterface $slugger;

    private HymnRepository $hymnRepository;

    private VerseRepository $verseRepository;

    private HymnNormalizer $hymnNormalizer;

    private VerseNormalizer $verseNormalizer;

    private BookService $bookService;

    public function __construct(
        SluggerInterface $slugger,
        HymnRepository $hymnRepository,
        VerseRepository $verseRepository,
        HymnNormalizer $hymnNormalizer,
        VerseNormalizer $verseNormalizer,
        BookService $bookService,
    ) {
        $this->slugger = $slugger;
        $this->hymnRepository = $hymnRepository;
        $this->verseRepository = $verseRepository;
        $this->hymnNormalizer = $hymnNormalizer;
        $this->verseNormalizer = $verseNormalizer;
        $this->bookService = $bookService;
    }

    public function getHymnsByBookId(string $bookId, int $startNumber, int $endNumber): ResultDto
    {
        $bookId = $this->bookService->getCorrectBookId($bookId, $endNumber);
        $hymns = $this->hymnRepository->getHymnsByBookId($bookId, $startNumber, $endNumber);
        $hymns = $this->hymnNormalizer->normalizeArray($hymns);

        return $this->makeResultDto(true, $hymns, 'Successfully retrieved hymns');
    }

    public function getHymnsWithVerses(string $bookId, int $startNumber, int $endNumber): ResultDto
    {
        $bookId = $this->bookService->getCorrectBookId($bookId, $endNumber);
        $hymns = $this->hymnRepository
            ->getHymnsWithVerses($bookId, $startNumber, $endNumber, self::HYMNS_WITH_VERSES_LIMIT);
        $hymns = $this->hymnNormalizer->normalizeArrayWithVerses($hymns);

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

    public function searchHymns(string $search): ResultDto
    {
        $search = trim(str_replace([',', '.', ':', ';', '  '], [' ', ' ', ' ', ' ', ' '], $search));

        if (is_numeric($search)) {
            $hymns = $this->hymnRepository->searchHymnsByNumber($search, self::SEARCH_RESULTS_LIMIT);
            $hymns = $this->hymnNormalizer->normalizeArrayWithFirstVerse($hymns);
        } else {
            $exploded = explode(' ', $search);
            $exploded = array_filter($exploded);
            $searchExpression = sprintf('%%%s%%', implode('%', $exploded));
            $limit = round(self::SEARCH_RESULTS_LIMIT / 2);

            $hymnsByTitle = $this->hymnRepository->searchHymnsByTitle($searchExpression, $limit);
            $hymnsByTitle = $this->hymnNormalizer->normalizeArrayWithFirstVerse($hymnsByTitle);

            $hymnsByLyrics = $this->verseRepository->searchVerses($searchExpression, $limit);
            $hymnsByLyrics = $this->verseNormalizer->normalizeArrayWithHymns($hymnsByLyrics);

            $hymns = $this->getUniqueHymnsFromResults($hymnsByTitle, $hymnsByLyrics);
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
            $uniqId = uniqid('', true) . uniqid('', true);
            $uniqId = str_replace(['.', '666'], ['', '555'], $uniqId);
            $string = sprintf('%s-%s', $number, mb_substr($title, 0, 35));
            $hymnId = mb_strtolower(mb_substr($this->slugger->slug($string) . $uniqId, 0, 50));
        }

        $hymn = new Hymn();
        $hymn->setId($hymnId)
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
                    'Припев:', 'Хор.', 'Хор:', 'Чол.', 'Жін.', 'Діс.', 'Діс:', 'Приспів', 'Бас.', 'Бас:',
                    'припев:', 'хор.', 'хор:', 'чол.', 'жін.', 'діс.', 'діс:', 'приспів', 'бас.', 'бас:',
                    '"', '«', '»', '(', ')', ':,:', ':', ';', '.', '–', '  ',
                ],
                [
                    '', '', '', '', '', '', '', '', '', '',
                    '', '', '', '', '', '', '', '', '', '',
                    '', '', '', '', '', '', ',', ',', ',', '', ' ',
                ],
                $matches[0]
            ), "\ \n\r\t\v\0,!'?-");

            $result[] = [
                'number' => $startHymnNumber === null ? $number : $startHymnNumber++,
                'name'   => $name,
                'lyrics' => $lyrics,
            ];
        }

        return $result;
    }
}
