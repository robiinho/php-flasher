<?php

declare(strict_types=1);

namespace Flasher\Tests\Prime\Storage\Bag;

use Flasher\Prime\Notification\Envelope;
use Flasher\Prime\Notification\Notification;
use Flasher\Prime\Storage\Bag\ArrayBag;
use Flasher\Tests\Prime\TestCase;

final class ArrayBagTest extends TestCase
{
    public function testArrayBag(): void
    {
        $bag = new ArrayBag();

        $envelopes = [
            new Envelope(new Notification()),
            new Envelope(new Notification()),
        ];

        $bag->set($envelopes);

        $this->assertEquals($envelopes, $bag->get());
    }
}
