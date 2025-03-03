<?php

namespace App\Service;

use App\Dto\ResultDto;
use App\Entity\Hymn;
use App\Entity\Verse;
use App\Repository\VerseRepository;

class VerseService extends Service
{
    private VerseRepository $verseRepository;

    public function __construct(VerseRepository $verseRepository)
    {
        $this->verseRepository = $verseRepository;
    }

    public function parseAndCreateVersesForHymn(
        Hymn $hymn,
        string $verses,
        bool $flush = true
    ): ResultDto
    {
        $verseList = explode("\n", $verses);
        $verseResultList = [];
        $position = 0;

        foreach ($verseList as $verse) {
            $matchesVerses = [];

            if (trim($verse) === '' || is_numeric(trim($verse))) {
                continue;
            }

            if (preg_match('/ *(Припев|Прыпеў|Приспів) *:? *(.+)/mui', $verse, $matchesVerses)) {
                [, , $lyrics] = $matchesVerses;
                $position = ((int) $position) + 1;

                $verseResultList[] = [
                    'position'  => $position,
                    'is_chorus' => true,
                    'lyrics'    => $lyrics,
                    'chords'    => '',
                ];
            } elseif (preg_match('/ *(\d+)? *\.? *(.+)/mu', $verse, $matchesVerses)) {
                [, $position, $lyrics] = $matchesVerses;
                $position = ((int) $position) ?: 1;

                $verseResultList[] = [
                    'position'  => $position,
                    'is_chorus' => false,
                    'lyrics'    => $lyrics,
                    'chords'    => '',
                ];
            }  else {
                return $this->makeResultDto(false, $verses, 'Verse cannot be parsed', 422);
            }
        }

        $result = [];

        foreach ($verseResultList as $verseResult) {
            $this->createVerse(
                $hymn,
                $verseResult['position'],
                $verseResult['is_chorus'],
                $verseResult['lyrics'],
                $verseResult['chords'],
                $flush
            );

            $result[] = sprintf('%d-%d-%d',
                (int) $hymn->getHymnId(), $verseResult['position'], (int) $verseResult['is_chorus']
            );
        }

        return $this->makeResultDto(true, $result, 'Successfully');
    }

    public function createVerse(
        Hymn $hymn,
        int $position,
        bool $isChorus,
        string $lyrics,
        string $chords,
        bool $flush = true
    ): Verse
    {
        $verse = new Verse();
        $verse->setHymn($hymn)
            ->setPosition($position)
            ->setIsChorus($isChorus)
            ->setLyrics(trim($lyrics))
            ->setChords(trim($chords));
        $this->verseRepository->add($verse, $flush);

        return $verse;
    }
}
