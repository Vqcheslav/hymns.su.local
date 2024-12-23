<?php

namespace App\Normalizer;

use App\Entity\Hymn;
use App\Entity\Verse;

class HymnNormalizer
{
    private VerseNormalizer $verseNormalizer;

    public function __construct(VerseNormalizer $verseNormalizer)
    {
        $this->verseNormalizer = $verseNormalizer;
    }

    public function normalize(Hymn $hymn): array
    {
        $bookId = '';
        $bookTitle = '';

        if ($hymn->getBook() !== null) {
            $bookId = $hymn->getBook()->getBookId();
            $bookTitle = $hymn->getBook()->getTitle();
        }

        return [
            'hymn_id'    => $hymn->getHymnId(),
            'book_id'    => $bookId,
            'book_title' => $bookTitle,
            'category'   => $hymn->getCategory(),
            'title'      => $hymn->getTitle(),
            'number'     => $hymn->getNumber(),
            'tone'       => $hymn->getTone(),
            'verses'     => [],
        ];
    }

    public function normalizeArray(array $hymns): array
    {
        $data = [];

        foreach ($hymns as $hymn) {
            $data[] = $this->normalize($hymn);
        }

        return $data;
    }

    public function normalizeWithFirstVerse(Hymn $hymn): array
    {
        $firstVerse = $hymn->getVerses()->first();
        $normalized = $this->normalize($hymn);
        $normalized['verses'] = [];

        if ($firstVerse instanceof Verse) {
            $normalized['verses'][] = $this->verseNormalizer->normalize($firstVerse);
        }

        return $normalized;
    }

    public function normalizeWithVerses(Hymn $hymn): array
    {
        $verses = $hymn->getVerses();
        $normalized = $this->normalize($hymn);
        $normalized['verses'] = [];

        foreach ($verses as $verse) {
            if ($verse instanceof Verse) {
                $normalized['verses'][] = $this->verseNormalizer->normalize($verse);
            }
        }

        return $normalized;
    }

    public function normalizeArrayWithFirstVerse(array $hymns): array
    {
        $data = [];

        foreach ($hymns as $hymn) {
            $data[] = $this->normalizeWithFirstVerse($hymn);
        }

        return $data;
    }

    public function normalizeArrayWithVerses($hymns): array
    {
        $data = [];

        foreach ($hymns as $hymn) {
            $data[] = $this->normalizeWithVerses($hymn);
        }

        return $data;
    }
}
