<?php

namespace App\Tests\Integration;

use App\Service\HymnsTelegramBotService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HymnsTelegramBotServiceTest extends KernelTestCase
{
    public function testGetTextFromHymn(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var HymnsTelegramBotService $hymnsTelegramBotService */
        $hymnsTelegramBotService = $container->get(HymnsTelegramBotService::class);

        $text = $hymnsTelegramBotService->getTextFromHymn([
            'hymn_id'    => 'title',
            'number'     => 1,
            'title'      => 'First',
            'book_title' => 'First',
            'category'   => 'First',
            'verses'     => [],
        ]);

        $this->assertEquals(477, strlen($text));
    }

    public function testGetSubmitErrorLink(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var HymnsTelegramBotService $hymnsTelegramBotService */
        $hymnsTelegramBotService = $container->get(HymnsTelegramBotService::class);

        $link = $hymnsTelegramBotService->getSubmitErrorLink(
            ['number' => 1, 'title' => 'First', 'book_title' => 'First', 'category' => 'First', 'verses' => []],
        );
        $expected =
            '<a href="https://t.me/vqcheslav?text=%D0%9E%D1%88%D0%B8%D0%B1%D0%BA%D0%B0%20%D0%B2%20%D1%82%D0%B5%D0%BA%D1%81%D1%82%D0%B5%20%D0%B3%D0%B8%D0%BC%D0%BD%D0%B0%3A%0AFirst%0A1%3A%20First%0A%0A%D0%9E%D0%BF%D0%B8%D1%81%D0%B0%D0%BD%D0%B8%D0%B5%20%D0%BE%D1%88%D0%B8%D0%B1%D0%BA%D0%B8%3A%20">Сообщить об ошибке</a>';

        $this->assertEquals($expected, $link);
    }

    public function testGetLinkForHymn(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var HymnsTelegramBotService $hymnsTelegramBotService */
        $hymnsTelegramBotService = $container->get(HymnsTelegramBotService::class);

        $link = $hymnsTelegramBotService->getLinkForHymn(['hymn_id' => 'title']);
        $expected = '<a href="http://localhost/#title">http://localhost/</a>';

        $this->assertEquals($expected, $link);
    }

    public function testProcessText(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var HymnsTelegramBotService $hymnsTelegramBotService */
        $hymnsTelegramBotService = $container->get(HymnsTelegramBotService::class);

        $resultMessage = $hymnsTelegramBotService->processText('1178');

        $this->assertNotEquals('', $resultMessage);
    }
}
