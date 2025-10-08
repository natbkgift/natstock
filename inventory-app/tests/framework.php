<?php

declare(strict_types=1);

namespace Tests\Framework {

use App\Application;
use AssertionError;
use Closure;

final class TestSuite
{
    private static ?self $instance = null;

    /** @var array<int, array{string, Closure}> */
    private array $tests = [];

    /** @var list<Closure> */
    private array $beforeEach = [];

    private ?object $currentUser = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function beforeEach(Closure $closure): self
    {
        $this->beforeEach[] = $closure;

        return $this;
    }

    public function addTest(string $description, Closure $test): void
    {
        $this->tests[] = [$description, $test];
    }

    public function run(): bool
    {
        $allPassed = true;

        foreach ($this->tests as [$description, $test]) {
            $this->reset();
            $context = new TestContext($this);

            foreach ($this->beforeEach as $callback) {
                $callback->call($context);
            }

            try {
                $test->call($context);
                fwrite(STDOUT, "✓ {$description}\n");
            } catch (\Throwable $throwable) {
                $allPassed = false;
                fwrite(STDERR, "✗ {$description}: {$throwable->getMessage()}\n");
            }
        }

        return $allPassed;
    }

    public function makeRequest(string $method, string $uri, ?object $user = null): TestResponse
    {
        return Application::handle($method, $uri, $user);
    }

    public function setCurrentUser(?object $user): void
    {
        $this->currentUser = $user;
    }

    public function currentUser(): ?object
    {
        return $this->currentUser;
    }

    private function reset(): void
    {
        $this->currentUser = null;
    }
}

final class TestContext
{
    public function __construct(private readonly TestSuite $suite)
    {
    }

    public function withoutVite(): void
    {
        // No assets are loaded in the simplified test environment.
    }
}

final class RequestBuilder
{
    public function __construct(private readonly TestSuite $suite, private readonly object $user)
    {
    }

    public function get(string $uri): TestResponse
    {
        return $this->suite->makeRequest('GET', $uri, $this->user);
    }
}

final class TestResponse
{
    private function __construct(
        private readonly int $status,
        private readonly string $body
    )
    {
    }

    public static function make(int $status, string $body): self
    {
        return new self($status, $body);
    }

    public function assertStatus(int $expected): self
    {
        if ($this->status !== $expected) {
            throw new AssertionError("Expected status {$expected} but received {$this->status}.");
        }

        return $this;
    }

    public function assertSee(string $expected): self
    {
        if (!str_contains($this->body, $expected)) {
            throw new AssertionError("Failed asserting that response contains '{$expected}'.");
        }

        return $this;
    }
}
}

namespace {

use Tests\Framework\RequestBuilder;
use Tests\Framework\TestResponse;
use Tests\Framework\TestSuite;

if (!function_exists('tests')) {
    function tests(): TestSuite
    {
        return TestSuite::getInstance();
    }
}

if (!function_exists('test')) {
    function test(string $description, \Closure $closure): void
    {
        tests()->addTest($description, $closure);
    }
}

if (!function_exists('uses')) {
    function uses(mixed ...$args): object
    {
        unset($args);

        return new class
        {
            public function in(string $directory): void
            {
                unset($directory);
            }
        };
    }
}

if (!function_exists('route')) {
    function route(string $name, array $parameters = []): string
    {
        return match ($name) {
            'admin.dashboard' => '/admin/dashboard',
            'admin.reports.index' => '/admin/reports',
            'admin.reports.expiring-batches' => '/admin/reports/expiring-batches',
            'admin.reports.low-stock' => '/admin/reports/low-stock',
            'admin.reports.valuation' => '/admin/reports/valuation',
            'admin.import.errors.download' => '/admin/import/error/' . ($parameters['token'] ?? ''),
            default => $name,
        };
    }
}

if (!function_exists('expect')) {
    function expect(mixed $value): object
    {
        return new class($value)
        {
            public function __construct(private mixed $value)
            {
            }

            public function toBe(mixed $expected): void
            {
                if ($this->value !== $expected) {
                    throw new \AssertionError(sprintf('Failed asserting that %s is identical to %s.', var_export($this->value, true), var_export($expected, true)));
                }
            }

            public function toBeInstanceOf(string $class): void
            {
                if (!($this->value instanceof $class)) {
                    throw new \AssertionError(sprintf('Failed asserting that value is instance of %s.', $class));
                }
            }

            public function toContain(string $needle): void
            {
                if (!is_string($this->value)) {
                    throw new \AssertionError('Failed asserting that non-string value contains substring.');
                }

                if (!str_contains($this->value, $needle)) {
                    throw new \AssertionError(sprintf('Failed asserting that "%s" contains "%s".', $this->value, $needle));
                }
            }

            public function toStartWith(string $prefix): void
            {
                if (!is_string($this->value) || !str_starts_with($this->value, $prefix)) {
                    throw new \AssertionError(sprintf('Failed asserting that value starts with "%s".', $prefix));
                }
            }
        };
    }
}

tests()->beforeEach(function (): void {
    foreach ([
        \App\Models\Category::class,
        \App\Models\Product::class,
        \App\Models\StockMovement::class,
        \App\Models\User::class,
    ] as $modelClass) {
        if (method_exists($modelClass, 'resetStubState')) {
            $modelClass::resetStubState();
        }
    }

    $tempDir = storage_path('app/tmp');
    if (is_dir($tempDir)) {
        foreach (glob($tempDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
});
}

namespace Pest\Laravel {

use Tests\Framework\RequestBuilder;
use Tests\Framework\TestResponse;
use Tests\Framework\TestSuite;

if (!function_exists('Pest\Laravel\actingAs')) {
    function actingAs(object $user): RequestBuilder
    {
        $suite = TestSuite::getInstance();
        $suite->setCurrentUser($user);

        return new RequestBuilder($suite, $user);
    }
}

if (!function_exists('Pest\Laravel\get')) {
    function get(string $uri): TestResponse
    {
        $suite = TestSuite::getInstance();

        return $suite->makeRequest('GET', $uri, $suite->currentUser());
    }
}
}
