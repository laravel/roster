<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Detectors\ApproachDetector;
use Laravel\Roster\Detectors\BrowserTestFrameworkDetector;
use Laravel\Roster\Detectors\FrontendDetector;
use Laravel\Roster\Detectors\StackDetector;
use Laravel\Roster\Detectors\StarterKitDetector;
use Laravel\Roster\Detectors\TestFrameworkDetector;
use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\StarterKit;
use Laravel\Roster\Enums\TestFramework;
use Laravel\Roster\Scanners\Composer;
use Laravel\Roster\Scanners\JsLockfile;
use Laravel\Roster\Support\EnumSet;

class Project
{
    /**
     * @param  EnumSet<Stack>  $stack
     * @param  EnumSet<BrowserTestFramework>  $browserTestFrameworks
     * @param  EnumSet<Frontend>  $frontend
     * @param  EnumSet<Agent>  $agents
     * @param  EnumSet<Approach>  $approach
     */
    public function __construct(
        protected PhpEcosystem $php,
        protected JsEcosystem $js,
        protected EnumSet $stack,
        protected ?TestFramework $testFramework,
        protected EnumSet $browserTestFrameworks,
        protected EnumSet $frontend,
        protected ?StarterKit $starterKit,
        protected EnumSet $agents,
        protected EnumSet $approach,
    ) {}

    public function php(): PhpEcosystem
    {
        return $this->php;
    }

    public function js(): JsEcosystem
    {
        return $this->js;
    }

    /** @return EnumSet<Stack> */
    public function stack(): EnumSet
    {
        return $this->stack;
    }

    public function testFramework(): ?TestFramework
    {
        return $this->testFramework;
    }

    /** @return EnumSet<BrowserTestFramework> */
    public function browserTestFrameworks(): EnumSet
    {
        return $this->browserTestFrameworks;
    }

    /** @return EnumSet<Frontend> */
    public function frontend(): EnumSet
    {
        return $this->frontend;
    }

    public function starterKit(): ?StarterKit
    {
        return $this->starterKit;
    }

    /** @return EnumSet<Agent> */
    public function agents(): EnumSet
    {
        return $this->agents;
    }

    /** @return EnumSet<Approach> */
    public function approach(): EnumSet
    {
        return $this->approach;
    }

    public static function scan(?string $basePath = null, ?Registry $registry = null): self
    {
        $registry ??= self::resolveRegistry();
        $basePath = self::normalizeBasePath($basePath);

        $phpPackages = (new Composer($basePath.'composer.lock', $registry))->scan();

        $jsLockfile = new JsLockfile($basePath, $registry);
        $jsPackages = $jsLockfile->scan();

        $php = new PhpEcosystem($phpPackages);
        $js = new JsEcosystem($jsPackages, $jsLockfile->committedManager());

        return new self(
            $php,
            $js,
            (new StackDetector)->detect($php, $js),
            (new TestFrameworkDetector)->detect($php),
            (new BrowserTestFrameworkDetector)->detect($php, $js),
            (new FrontendDetector)->detect($js),
            (new StarterKitDetector($basePath))->detect($php),
            new EnumSet(AgentsDetector::configured($basePath)),
            (new ApproachDetector($basePath))->detect(),
        );
    }

    public static function normalizeBasePath(?string $basePath): string
    {
        $resolved = $basePath ?? (function_exists('base_path') ? base_path() : (getcwd() ?: '.'));

        return Str::finish($resolved, DIRECTORY_SEPARATOR);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'php' => array_map(fn (Package $p): array => $p->toArray(), $this->php->packages()->all()),
            'js' => array_map(fn (Package $p): array => $p->toArray(), $this->js->packages()->all()),
            'stack' => $this->stack->values(),
            'testFramework' => $this->testFramework?->value,
            'browserTestFrameworks' => $this->browserTestFrameworks->values(),
            'frontend' => $this->frontend->values(),
            'starterKit' => $this->starterKit?->value,
            'approach' => $this->approach->values(),
            'agents' => $this->agents->values(),
            'jsPackageManager' => $this->js->packageManager()?->value,
        ];
    }

    public function json(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT) ?: '{}';
    }

    private static function resolveRegistry(): Registry
    {
        try {
            /** @var Registry */
            return Container::getInstance()->make(Registry::class);
        } catch (BindingResolutionException) {
            return new Registry;
        }
    }
}
