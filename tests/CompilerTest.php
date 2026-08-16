<?php

declare(strict_types=1);

namespace Luxid\Nova\Tests;

use Luxid\Nova\Compiler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for template compilation and execution.
 *
 * @package Luxid\Nova\Tests
 */
final class CompilerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Compiler::clearCache();
    }

    /**
     * Compile and run a template against a scope.
     *
     * @param string               $template Raw template
     * @param array<string, mixed> $scope    Variables exposed to the template
     */
    private function render(string $template, array $scope = []): string
    {
        return Compiler::render($template, 'test:' . hash('xxh128', $template), $scope);
    }

    #[Test]
    public function it_passes_plain_markup_through(): void
    {
        $this->assertSame('<p>hello</p>', $this->render('<p>hello</p>'));
    }

    #[Test]
    public function it_escapes_an_echo_directive(): void
    {
        $output = $this->render('@echo($state->name)', ['state' => (object) ['name' => '<script>']]);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    #[Test]
    public function it_tolerates_a_null_value_in_echo(): void
    {
        $this->assertSame('', $this->render('@echo($state->missing)', ['state' => (object) ['missing' => null]]));
    }

    #[Test]
    public function raw_output_is_not_escaped(): void
    {
        $output = $this->render('@raw($state->html)', ['state' => (object) ['html' => '<b>hi</b>']]);

        $this->assertSame('<b>hi</b>', $output);
    }

    #[Test]
    public function it_compiles_a_conditional(): void
    {
        $template = '@if($state->on)yes@else no@endif';

        $this->assertSame('yes', $this->render($template, ['state' => (object) ['on' => true]]));
        $this->assertSame(' no', $this->render($template, ['state' => (object) ['on' => false]]));
    }

    #[Test]
    public function it_compiles_a_foreach_over_a_nested_call(): void
    {
        // Regression: the directive matched with [^)]+, so any expression
        // containing parentheses truncated at the first closing bracket.
        $output = $this->render(
            '@foreach(array_keys($state->items) as $key)@echo($key)@endforeach',
            ['state' => (object) ['items' => ['a' => 1, 'b' => 2]]]
        );

        $this->assertSame('ab', $output);
    }

    #[Test]
    public function it_compiles_a_for_loop_with_a_function_call(): void
    {
        $output = $this->render(
            '@for($i = 0; $i < count($state->items); $i++)x@endfor',
            ['state' => (object) ['items' => [1, 2, 3]]]
        );

        $this->assertSame('xxx', $output);
    }

    #[Test]
    public function it_converts_event_attributes_into_data_attributes(): void
    {
        $this->assertSame('<form data-nova-submit="save">', $this->render('<form @submit="save">'));
        $this->assertSame('<button data-nova-click="go">', $this->render('<button @click="go">'));
        $this->assertSame('<input data-nova-input="sync">', $this->render('<input @input="sync">'));
    }

    #[Test]
    public function it_escapes_an_event_action_name(): void
    {
        $this->assertStringNotContainsString('"><script>', $this->render('<form @submit="a&quot;><script>">'));
    }

    #[Test]
    public function it_writes_a_compiled_file_rather_than_calling_eval(): void
    {
        $path = Compiler::compiledFile('@echo($state->x)', 'compiled-file-test');

        $this->assertFileExists($path);
        $this->assertStringContainsString('htmlspecialchars', (string) file_get_contents($path));
    }

    #[Test]
    public function it_reuses_a_compiled_file_across_renders(): void
    {
        $first = Compiler::compiledFile('<p>a</p>', 'reuse-test');
        $second = Compiler::compiledFile('<p>a</p>', 'reuse-test');

        $this->assertSame($first, $second);
    }

    #[Test]
    public function it_closes_the_output_buffer_when_a_template_throws(): void
    {
        $depth = ob_get_level();

        try {
            $this->render('@if(throwingHelper())x@endif');
        } catch (\Throwable) {
            // Expected: the helper does not exist.
        }

        $this->assertSame($depth, ob_get_level());
    }

    #[Test]
    public function it_reports_cache_statistics(): void
    {
        Compiler::compiledFile('<p>stats</p>', 'stats-test');
        $stats = Compiler::getCacheStats();

        $this->assertArrayHasKey('file_cache_path', $stats);
    }
}
