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
    ) {
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

function tests(): TestSuite
{
    return TestSuite::getInstance();
}

function test(string $description, \Closure $closure): void
{
    tests()->addTest($description, $closure);
}

function uses(mixed ...$args): void
{
    // Traits are not required in the simplified environment.
}

function route(string $name): string
{
    return match ($name) {
        'admin.dashboard' => '/admin/dashboard',
        default => $name,
    };
}
}

namespace Pest\Laravel {

use Tests\Framework\RequestBuilder;
use Tests\Framework\TestResponse;
use Tests\Framework\TestSuite;

function actingAs(object $user): RequestBuilder
{
    $suite = TestSuite::getInstance();
    $suite->setCurrentUser($user);

    return new RequestBuilder($suite, $user);
}

function get(string $uri): TestResponse
{
    $suite = TestSuite::getInstance();

    return $suite->makeRequest('GET', $uri, $suite->currentUser());
}
}

namespace Illuminate\Foundation\Testing {

trait RefreshDatabase
{
}
}

