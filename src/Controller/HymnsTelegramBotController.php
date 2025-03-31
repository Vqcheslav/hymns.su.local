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
    ) {}

    #[Route("/bot/telegram", name: "hymns_telegram_bot", methods: ["GET", "HEAD", "POST"])]
    public function processMessage(Request $request, LoggerInterface $logger): JsonResponse
    {
        $data = $request->toArray();

        $logger->info(json_encode($data));

        if (empty($data['message']['chat']['id']) || empty($data['message']['text'])) {
            return $this->jsonResponse(false, [], 'Empty message in request', 422);
        }

        $resultDto = $this->hymnsTelegramBotService->processMessage($data);

        if ($resultDto->hasErrors()) {
            $logger->warning($resultDto);
        }

        return $this->jsonResponseFromDto($resultDto);
    }
}
