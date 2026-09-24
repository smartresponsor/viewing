<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\ViewJsonSerializer;
use PHPUnit\Framework\TestCase;

final class ViewJsonSerializerTest extends TestCase
{
    public function testInvalidUtf8IsSubstitutedWithoutPartialOutput(): void
    {
        $serializer = new ViewJsonSerializer();
        $json = $serializer->serialize(['value' => "\xB1\x31"]);

        self::assertStringContainsString('\ufffd1', strtolower($json));
    }

    public function testRecursivePayloadThrowsJsonException(): void
    {
        $serializer = new ViewJsonSerializer();
        $data = [];
        $data['self'] = &$data;
        $this->expectException(\JsonException::class);

        $serializer->serialize($data);
    }
}
