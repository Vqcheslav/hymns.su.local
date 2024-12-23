<?php

namespace App\Normalizer;

use App\Entity\Book;

class BookNormalizer
{
    public function normalize(Book $book): array
    {
        return [
            'book_id'     => $book->getBookId(),
            'title'       => $book->getTitle(),
            'total_songs' => $book->getTotalSongs(),
        ];
    }

    public function normalizeArray(array $books): array
    {
        $data = [];

        foreach ($books as $entity) {
            $data[$entity->getBookId()] = $this->normalize($entity);
        }

        return $data;
    }
}
