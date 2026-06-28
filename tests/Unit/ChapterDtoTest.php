<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Chapters\DTOs\ChapterDto;
use App\Domains\Chapters\Models\Chapter;
use PHPUnit\Framework\TestCase;

class ChapterDtoTest extends TestCase
{
    public function test_chapter_dto_can_be_instantiated_with_pages_count(): void
    {
        $dto = new ChapterDto(
            id: '1234',
            title: 'Test Chapter',
            chapterNumber: '1.5',
            volumeNumber: '1',
            language: 'en',
            pagesCount: 15
        );

        $this->assertEquals('1234', $dto->id);
        $this->assertEquals('Test Chapter', $dto->title);
        $this->assertEquals('1.5', $dto->chapterNumber);
        $this->assertEquals('1', $dto->volumeNumber);
        $this->assertEquals('en', $dto->language);
        $this->assertEquals(15, $dto->pagesCount);

        $array = $dto->toArray();
        $this->assertEquals([
            'id' => '1234',
            'title' => 'Test Chapter',
            'chapter_number' => '1.5',
            'volume_number' => '1',
            'language' => 'en',
            'pages_count' => 15,
        ], $array);
    }

    public function test_chapter_dto_can_be_created_from_model(): void
    {
        $chapter = new Chapter();
        $chapter->id = '5678';
        $chapter->title = 'Another Chapter';
        $chapter->chapter_number = '2';
        $chapter->volume_number = null;
        $chapter->language = 'pt-br';
        $chapter->pages_count = 23;

        $dto = ChapterDto::fromModel($chapter);

        $this->assertEquals('5678', $dto->id);
        $this->assertEquals('Another Chapter', $dto->title);
        $this->assertEquals('2', $dto->chapterNumber);
        $this->assertNull($dto->volumeNumber);
        $this->assertEquals('pt-br', $dto->language);
        $this->assertEquals(23, $dto->pagesCount);
    }
}
