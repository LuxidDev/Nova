<?php

declare(strict_types=1);

namespace Luxid\Nova\Tests;

use InvalidArgumentException;
use Luxid\Nova\ActionDispatcher;
use Luxid\Nova\ComponentManager;
use Luxid\Nova\Csrf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

require_once __DIR__ . '/../src/helpers.php';

/**
 * Tests for the component action endpoint's input validation.
 *
 * @package Luxid\Nova\Tests
 */
final class ActionDispatcherTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ComponentManager::clear();
        ActionDispatcher::verifyCsrf();
        $_SESSION = [];

        component('counter', function ($c): void {
            $c->state(fn (): array => ['count' => 0]);
            $c->actions([
                'increment' => function (array &$state): void {
                    ++$state['count'];
                },
            ]);
            $c->view(function ($state): void {
                echo 'count';
            });
        });
    }

    protected function tearDown(): void
    {
        $_SESSION = [];

        parent::tearDown();
    }

    /**
     * Build a valid action payload, overriding individual fields.
     *
     * @param array<string, mixed> $overrides Fields to replace
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'component' => 'counter',
            'action' => 'increment',
            'instance' => 'counter_abc123',
            'params' => [],
            '_token' => Csrf::token(),
        ], $overrides);
    }

    #[Test]
    public function it_recognises_an_action_payload(): void
    {
        $this->assertTrue(ActionDispatcher::handles($this->payload()));
        $this->assertFalse(ActionDispatcher::handles(['component' => 'counter']));
    }

    #[Test]
    public function it_dispatches_a_valid_call(): void
    {
        $html = ActionDispatcher::dispatch($this->payload());

        $this->assertStringContainsString('data-nova-component', $html);
    }

    #[Test]
    public function it_rejects_a_call_with_no_csrf_token(): void
    {
        // Regression: the endpoint dispatched whatever was posted; the client
        // sent a token but nothing on the server ever compared it.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSRF');

        ActionDispatcher::dispatch($this->payload(['_token' => null]));
    }

    #[Test]
    public function it_rejects_a_call_with_a_wrong_csrf_token(): void
    {
        $this->expectException(RuntimeException::class);

        ActionDispatcher::dispatch($this->payload(['_token' => 'not-the-token']));
    }

    #[Test]
    public function it_rejects_an_unregistered_component(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not registered');

        ActionDispatcher::dispatch($this->payload(['component' => 'admin-panel']));
    }

    #[Test]
    public function it_rejects_an_action_the_component_does_not_declare(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('no action');

        ActionDispatcher::dispatch($this->payload(['action' => 'destroyEverything']));
    }

    #[Test]
    public function it_rejects_a_malformed_instance_id(): void
    {
        // The instance id indexes straight into the session, so a free-form
        // value would let a caller address arbitrary session keys.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Malformed');

        ActionDispatcher::dispatch($this->payload(['instance' => "evil'; DROP\n\n"]));
    }

    #[Test]
    public function it_rejects_non_array_parameters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ActionDispatcher::dispatch($this->payload(['params' => 'not-an-array']));
    }

    #[Test]
    public function it_requires_both_a_component_and_an_action(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ActionDispatcher::dispatch($this->payload(['component' => '']));
    }

    #[Test]
    public function it_can_be_run_without_csrf_verification(): void
    {
        ActionDispatcher::withoutCsrfVerification();

        try {
            $html = ActionDispatcher::dispatch($this->payload(['_token' => null]));
            $this->assertStringContainsString('data-nova-component', $html);
        } finally {
            ActionDispatcher::verifyCsrf();
        }
    }
}
