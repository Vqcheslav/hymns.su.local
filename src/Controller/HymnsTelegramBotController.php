<?php

namespace App\Controller;

use App\Service\HymnsTelegramBotService;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HymnsTelegramBotController extends Controller
{
    public function __construct(
        private readonly HymnsTelegramBotService $hymnsTelegramBotService,
        private readonly LoggerInterface $logger
    ) {}

    #[Route("/bot/telegram", name: "hymns_telegram_bot", methods: ["GET", "HEAD", "POST"])]
    public function processMessage(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $this->logger->info(json_encode($data));

        $resultDto = $this->hymnsTelegramBotService->processMessage($data);

        if ($resultDto->hasErrors()) {
            $this->logger->warning($resultDto);
        }

        return $this->jsonResponseFromDto($resultDto);
    }
}
