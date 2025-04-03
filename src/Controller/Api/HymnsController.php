<?php

namespace App\Controller\Api;

use App\Controller\Controller;
use App\Service\HymnService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HymnsController extends  Controller
{
    private HymnService $hymnsService;

    public function __construct(HymnService $hymnsService)
    {
        $this->hymnsService = $hymnsService;
    }

    #[Route("/api/v1/categories", name: "hymns.getCategories", methods: ["GET"])]
    public function getHymnCategories(): Response
    {
        $resultDto = $this->hymnsService->getHymnCategories();

        return $this->jsonResponseFromDto($resultDto);
    }

    #[Route("/api/v1/hymns/book/{bookId}/{startNumber<\d+>}/{endNumber<\d+>}", name: "hymns.getHymnsByBookId", methods: ["GET"])]
    public function getHymnsByBookId(string $bookId, int $startNumber, int $endNumber): Response
    {
        $resultDto = $this->hymnsService->getHymnsByBookId($bookId, $startNumber, $endNumber);

        return $this->jsonResponseFromDto($resultDto);
    }

    #[Route("/api/v1/hymns/verses/{bookId}/{startNumber<\d+>}/{endNumber<\d+>}", name: "hymns.getHymnsWithVerses", methods: ["GET"])]
    public function getHymnsWithVerses(string $bookId, int $startNumber, int $endNumber): Response
    {
        $resultDto = $this->hymnsService->getHymnsWithVerses($bookId, $startNumber, $endNumber);

        return $this->jsonResponseFromDto($resultDto);
    }

    #[Route("/api/v1/hymns/category/{offset<\d+>}/{limit<\d+>}", name: "hymns.getHymnsByCategory", methods: ["GET"])]
    public function getHymnsByCategory(int $offset, int $limit, Request $request): Response
    {
        $category = (string) $request->get('category', '');
        $resultDto = $this->hymnsService->getHymnsByCategory($category, $offset, $limit);

        return $this->jsonResponseFromDto($resultDto);
    }

    #[Route("/api/v1/hymns/updated/{afterDate<[0-9-TZ: ]+>}", name: "hymns.getUpdatedHymns", methods: ["GET"])]
    public function getUpdatedHymns(string $afterDate): Response
    {
        $resultDto = $this->hymnsService->getUpdatedHymns($afterDate);

        return $this->jsonResponseFromDto($resultDto);
    }

    #[Route("/api/v1/hymns/{hymnId<[a-z0-9-]+>}", name: "hymns.getHymnByHymnId", methods: ["GET"])]
    public function getHymnByHymnId(string $hymnId): Response
    {
        $resultDto = $this->hymnsService->getHymnByHymnId($hymnId);

        return $this->jsonResponseFromDto($resultDto);
    }

    #[Route("/api/v1/hymns/search/{search?}", name: "hymns.searchHymns", methods: ["GET"])]
    public function searchHymns(?string $search): Response
    {
        $resultDto = $this->hymnsService->searchHymns($search);

        return $this->jsonResponseFromDto($resultDto);
    }
}
