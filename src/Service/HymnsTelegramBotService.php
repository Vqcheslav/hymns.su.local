<?php

namespace App\Service;

use App\Dto\ResultDto;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class HymnsTelegramBotService extends Service
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function processMessage(array $data): ResultDto
    {
        $token = $_SERVER['TELEGRAM_BOT_TOKEN'];
        $queryParams = [
            'chat_id' => $data['message']['chat']['id'],
            'text'    => $data['message']['text'],
        ];

        try {
            $this->httpClient->request(
                'GET',
                sprintf(
                    'https://api.telegram.org/bot%s/sendMessage?%s',
                    $token,
                    http_build_query($queryParams),
                ),
            );
        } catch (Throwable $e) {
            return $this->makeResultDto(false, [], $e->getMessage(), 500);
        }

        return $this->makeResultDto(true, [], 'Successfully sent message');
    }
}
