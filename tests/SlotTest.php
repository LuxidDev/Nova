<?php

declare(strict_types=1);

namespace Luxid\Nova\Tests;

use Luxid\Nova\Slot;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for slot capture and its cleanup.
 *
 * @package Luxid\Nova\Tests
 */
final class SlotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Slot::reset();
    }

    protected function tearDown(): void
    {
        Slot::reset();

        parent::tearDown();
    }

    #[Test]
    public function it_captures_and_renders_a_slot(): void
    {
        Slot::start('content');
        echo 'hello';
        Slot::end();

        $this->assertSame('hello', Slot::render('content'));
        $this->assertTrue(Slot::has('content'));
    }

    #[Test]
    public function it_returns_the_default_for_an_unfilled_slot(): void
    {
        $this->assertSame('fallback', Slot::render('missing', 'fallback'));
        $this->assertFalse(Slot::has('missing'));
    }

    #[Test]
    public function a_frozen_slot_is_not_overwritten(): void
    {
        Slot::start('content');
        echo 'from the caller';
        Slot::end();

        Slot::freeze();

        Slot::start('content');
        echo 'component default';
        Slot::end();

        $this->assertSame('from the caller', Slot::render('content'));
    }

    #[Test]
    public function thaw_clears_captured_content(): void
    {
        Slot::start('content');
        echo 'x';
        Slot::end();

        Slot::thaw();

        $this->assertFalse(Slot::has('content'));
    }

    #[Test]
    public function an_aborted_capture_leaves_a_buffer_open(): void
    {
        // Documents why reset() exists: a template that throws between start()
        // and end() never closes its buffer, and PHP has no automatic cleanup.
        $baseline = ob_get_level();

        try {
            Slot::start('content');
            echo 'partial';

            throw new \RuntimeException('template exploded');
        } catch (\RuntimeException) {
            // The framework catches this and renders an error page.
        }

        $this->assertSame(1, Slot::openCaptures());
        $this->assertSame($baseline + 1, ob_get_level());

        Slot::reset();

        $this->assertSame($baseline, ob_get_level());
    }

    #[Test]
    public function reset_closes_every_leaked_buffer(): void
    {
        // Under a worker these accumulate for the life of the process, and each
        // one swallows the output of every later response.
        $baseline = ob_get_level();

        for ($i = 0; $i < 5; $i++) {
            try {
                Slot::start('content');

                throw new \RuntimeException('template exploded');
            } catch (\RuntimeException) {
            }
        }

        $this->assertSame(5, Slot::openCaptures());
        $this->assertSame($baseline + 5, ob_get_level());

        Slot::reset();

        $this->assertSame(0, Slot::openCaptures());
        $this->assertSame($baseline, ob_get_level());
    }

    #[Test]
    public function reset_discards_captured_content(): void
    {
        Slot::start('content');
        echo 'from a previous request';
        Slot::end();

        Slot::reset();

        $this->assertFalse(Slot::has('content'));
        $this->assertSame('', Slot::render('content'));
    }
}
