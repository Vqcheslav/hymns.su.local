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

        $text = $hymnsTelegramBotService->getTextFromHymn(
            ['number' => 1, 'title' => 'First', 'book_title' => 'First', 'category' => 'First', 'verses' => []],
        );

        $this->assertEquals(91, strlen($text));
    }
}
