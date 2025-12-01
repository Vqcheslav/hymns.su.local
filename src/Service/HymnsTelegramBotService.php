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

    public const string COMMAND_START = '/start';

    public const string COMMAND_HELP = '/help';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly HymnService $hymnService,
    ) {}

    public function processMessage(array $data): ResultDto
    {
        $chatId = $data['message']['chat']['id'] ?? $data['edited_message']['chat']['id'] ?? 0;
        $messageText = (string) ($data['message']['text'] ?? $data['edited_message']['text'] ?? self::COMMAND_HELP);

        if (empty($chatId)) {
            return $this->makeResultDto(false, $data, 'Empty message chat id in request', 422);
        }

        if (str_starts_with($messageText, '/')) {
            $resultMessage = $this->processCommand($messageText);
        } else {
            $resultMessage = $this->processText($messageText);
        }

        return $this->sendMessage($chatId, $resultMessage);
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

    private function sendMessage(int $chatId, string $text): ResultDto
    {
        $queryParams = [
            'chat_id'                  => $chatId,
            'text'                     => $text,
            'parse_mode'               => 'html',
            'disable_web_page_preview' => true,
        ];

        try {
            $url = sprintf('https://api.telegram.org/bot%s/sendMessage', $_SERVER['TELEGRAM_BOT_TOKEN']);

            $response = $this->httpClient->request('GET', $url, ['query' => $queryParams]);
            $responseData = $response->toArray();

            if (($responseData['ok'] ?? false) === false) {
                return $this->makeResultDto(false, $responseData, $responseData['description'] ?? 'Error', 500);
            }
        } catch (Throwable $e) {
            return $this->makeResultDto(false, $queryParams, $e->getMessage(), 500);
        }

        return $this->makeResultDto(true, $queryParams, 'Successfully sent message');
    }

    public function processCommand(string $messageText): string
    {
        if ($messageText === self::COMMAND_START || $messageText === self::COMMAND_HELP) {
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

        return $resultMessage;
    }

    public function processText(string $messageText): string
    {
        $hymnsResultDto = $this->hymnService->searchHymns($messageText, self::SEARCH_RESULTS_LIMIT);
        $hymns = $hymnsResultDto->getData();

        if ($hymnsResultDto->hasErrors()) {
            return $hymnsResultDto->getDetail();
        }

        if (empty($hymns)) {
            return 'Ничего не найдено';
        }

        if (count($hymns) === 1) {
            return $this->processCommand('/' . $hymns[0]['hymn_id']);
        }

        return $this->getMessageWithActionsFromHymnsArray($hymns);
    }
}
