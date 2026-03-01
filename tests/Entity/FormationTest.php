<?php

namespace App\Tests\Entity;

use App\Entity\Formation;
use PHPUnit\Framework\TestCase;

class FormationTest extends TestCase
{
    public function testGetPublishedAtStringEmpty(): void
    {
        $formation = new Formation();

        self::assertSame('', $formation->getPublishedAtString());
    }

    public function testGetPublishedAtString(): void
    {
        $formation = new Formation();
        $formation->setPublishedAt(new \DateTime('2024-05-13'));

        self::assertSame('13/05/2024', $formation->getPublishedAtString());
    }
}
