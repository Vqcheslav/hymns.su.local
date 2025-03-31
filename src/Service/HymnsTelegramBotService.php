<?php

namespace App\Service;

use App\Dto\ResultDto;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class HymnsTelegramBotService extends Service
{
    public const int SEARCH_RESULTS_LIMIT = 16;

    public const string DESCRIPTION = "1. Отправьте номер или текст, в ответ бот отдаст список результатов. 
            \n2. Затем нажмите на выделенное кодовое слово (начинается с /) под нужным гимном для просмотра.";

    public const string SUBMIT_USERNAME = '@vqcheslav';

    public const string BOT_USERNAME = '@hymns_telegram_bot';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly HymnService $hymnService,
    ) {}

    public function processMessage(array $data): ResultDto
    {
        $messageText = $data['message']['text'];

        if (str_starts_with($messageText, '/')) {
            if ($messageText === '/start' || $messageText === '/help') {
                $resultMessage = self::DESCRIPTION;
            } else {
                $hymnId = str_replace('_', '-', substr($messageText, 1));
                $hymnResultDto = $this->hymnService->getHymnByHymnId($hymnId);

                if ($hymnResultDto->hasErrors()) {
                    $resultMessage = $hymnResultDto->getDetail();
                } else {
                    $resultMessage = $this->getTextFromHymn($hymnResultDto->getData());
                }
            }
        } else {
            $hymnsResultDto = $this->hymnService->searchHymns($messageText, self::SEARCH_RESULTS_LIMIT);
            $hymns = $hymnsResultDto->getData();

            if ($hymnsResultDto->hasErrors()) {
                $resultMessage = $hymnsResultDto->getDetail();
            } elseif (empty($hymns)) {
                $resultMessage = 'Ничего не найдено';
            } else {
                $resultMessage = $this->getMessageWithActionsFromHymnsArray($hymns);
            }
        }

        return $this->sendMessage($data['message']['chat']['id'], $resultMessage, $data['message']['message_id']);
    }

    public function getMessageWithActionsFromHymnsArray(array $hymns): string
    {
        $result = [];

        foreach ($hymns as $hymn) {
            $action = '/' . str_replace('-', '_', $hymn['hymn_id']);
            $result[] = sprintf("%s\n%s", $this->getHeaderOfHymn($hymn), $action);
        }

        return implode("\n\n", $result);
    }

    public function getTextFromHymn(array $hymn): string
    {
        $lyrics = '';

        foreach ($hymn['verses'] as $verse) {
            if ($verse['is_chorus']) {
                $lyrics .= sprintf("<i>Припев:</i>\n%s", htmlspecialchars($verse['lyrics']));
            } else {
                $lyrics .= sprintf('%d. %s', $verse['position'], htmlspecialchars($verse['lyrics']));
            }

            $lyrics .= "\n\n";
        }

        return sprintf(
            "%s\n\n\n%s\n%s\nБот: %s",
            $this->getHeaderOfHymn($hymn),
            $lyrics,
            $this->getSubmitErrorLink($hymn),
            self::BOT_USERNAME,
        );
    }

    public function getHeaderOfHymn(array $hymn): string
    {
        $bookTitleAndCategory = sprintf(
            '<strong>%s</strong> :: %s',
            htmlspecialchars($hymn['book_title']),
            htmlspecialchars($hymn['category']),
        );
        $numberAndTitle = sprintf('<strong>%d</strong>: %s…', $hymn['number'], htmlspecialchars($hymn['title']));

        return sprintf("%s\n%s", $bookTitleAndCategory, $numberAndTitle);
    }

    public function getSubmitErrorLink(array $hymn): string
    {
        $usernameForLink = str_replace('@', '', self::SUBMIT_USERNAME);
        $parameters = [
            'text' => sprintf(
                "%s:\n%s\n%d: %s\n\n%s: ",
                'Ошибка в тексте гимна',
                $hymn['book_title'],
                $hymn['number'],
                $hymn['title'],
                'Описание ошибки',
            ),
        ];

        $queryParameters = http_build_query(data: $parameters, encoding_type: PHP_QUERY_RFC3986);
        $url = sprintf('https://t.me/%s?%s', $usernameForLink, $queryParameters);

        return sprintf('<a href="%s">%s</a>', $url, 'Сообщить об ошибке');
    }

    private function sendMessage(int $chatId, string $text, int $replyTo = null, string $parseMode = 'html'): ResultDto
    {
        try {
            $responseQueryParams = [
                'chat_id'                  => $chatId,
                'text'                     => $text,
                'parse_mode'               => $parseMode,
                'reply_to_message_id'      => $replyTo,
                'disable_web_page_preview' => true,
            ];

            $this->httpClient->request(
                'GET',
                sprintf(
                    'https://api.telegram.org/bot%s/sendMessage?%s',
                    $_SERVER['TELEGRAM_BOT_TOKEN'],
                    http_build_query($responseQueryParams),
                ),
            );
        } catch (Throwable $e) {
            return $this->makeResultDto(false, [], $e->getMessage(), 500);
        }

        return $this->makeResultDto(true, [], 'Successfully sent message');
    }
}
