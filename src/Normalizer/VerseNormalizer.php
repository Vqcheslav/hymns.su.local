<?php

namespace App\Normalizer;

use App\Entity\Hymn;
use App\Entity\Verse;

class VerseNormalizer
{
    public function normalize(Verse $verse): array
    {
        return [
            'verse_id'  => $verse->getVerseId(),
            'position'  => $verse->getPosition(),
            'is_chorus' => $verse->isChorus(),
            'lyrics'    => $verse->getLyrics(),
            'chords'    => $verse->getChords(),
        ];
    }

    public function normalizeArray(array $verses): array
    {
        $data = [];

        foreach ($verses as $verse) {
            $data[] = $this->normalize($verse);
        }

        return $data;
    }

    public function normalizeWithHymn(Verse $verse): array
    {
        $hymn = $verse->getHymn();

        if (! $hymn instanceof Hymn) {
            return [];
        }

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
            'verses'     => [$this->normalize($verse)],
        ];
    }

    public function normalizeArrayWithHymns(array $verses): array
    {
        $data = [];

        foreach ($verses as $verse) {
            $data[] = $this->normalizeWithHymn($verse);
        }

        return $data;
    }
}
