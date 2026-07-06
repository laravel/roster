<?php

namespace Laravel\Roster;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Str;
use Laravel\Roster\Detectors\AgentsDetection;
use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Detectors\ApproachDetector;
use Laravel\Roster\Detectors\BrowserTestFrameworkDetector;
use Laravel\Roster\Detectors\FrontendDetector;
use Laravel\Roster\Detectors\PackageManagersDetector;
use Laravel\Roster\Detectors\StackDetector;
use Laravel\Roster\Detectors\StarterKitDetector;
use Laravel\Roster\Detectors\TestFrameworkDetector;
use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Ecosystems\PhpEcosystem;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Enums\StarterKit;
use Laravel\Roster\Enums\TestFramework;
use Laravel\Roster\Scanners\Composer;
use Laravel\Roster\Scanners\JsLockfile;
use Laravel\Roster\Support\EnumSet;

class Roster
{
    /**
     * @param  EnumSet<Stack>  $stack
     * @param  EnumSet<BrowserTestFramework>  $browserTestFrameworks
     * @param  EnumSet<Frontend>  $frontend
     * @param  EnumSet<StarterKit>  $starterKit
     * @param  EnumSet<Approach>  $approach
     */
    public function __construct(
        protected PhpEcosystem $php,
        protected JsEcosystem $js,
        protected EnumSet $stack,
        protected ?TestFramework $testFramework,
        protected EnumSet $browserTestFrameworks,
        protected EnumSet $frontend,
        protected EnumSet $starterKit,
        protected AgentsDetection $agents,
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

    /** @return EnumSet<StarterKit> */
    public function starterKit(): EnumSet
    {
        return $this->starterKit;
    }

    public function agents(): AgentsDetection
    {
        return $this->agents;
    }

    /** @return EnumSet<Approach> */
    public function approach(): EnumSet
    {
        return $this->approach;
    }

    public static function scan(?string $basePath = null, bool $detectSystem = true, ?Registry $registry = null): self
    {
        $registry ??= self::resolveRegistry();
        $basePath = self::normalizeBasePath($basePath);

        $phpPackages = (new Composer($basePath.'composer.lock', $registry))->scan();

        $jsLockfile = new JsLockfile($basePath, $registry);
        $jsPackages = $jsLockfile->scan();

        $packageManagers = (new PackageManagersDetector($basePath, $detectSystem))
            ->detect($jsLockfile->committedManager());

        $php = new PhpEcosystem($phpPackages);
        $js = new JsEcosystem($jsPackages, $packageManagers);

        return new self(
            $php,
            $js,
            (new StackDetector)->detect($php, $js),
            (new TestFrameworkDetector)->detect($php),
            (new BrowserTestFrameworkDetector)->detect($php, $js),
            (new FrontendDetector)->detect($js),
            (new StarterKitDetector($basePath))->detect($php),
            (new AgentsDetector($basePath, $detectSystem))->detect(),
            (new ApproachDetector($basePath))->detect(),
        );
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

    public static function normalizeBasePath(?string $basePath): string
    {
        $resolved = $basePath ?? (function_exists('base_path') ? base_path() : (getcwd() ?: '.'));

        return Str::finish($resolved, DIRECTORY_SEPARATOR);
    }

    public function json(): string
    {
        $payload = [
            'php' => array_map(fn (Package $p): array => $p->toArray(), $this->php->packages()->all()),
            'js' => array_map(fn (Package $p): array => $p->toArray(), $this->js->packages()->all()),
            'stack' => $this->stack->values(),
            'testFramework' => $this->testFramework?->value,
            'browserTestFrameworks' => $this->browserTestFrameworks->values(),
            'frontend' => $this->frontend->values(),
            'starterKit' => $this->starterKit->values(),
            'approach' => $this->approach->values(),
            'agents' => [
                'configured' => $this->agents->configured()->values(),
                'installed' => $this->agents->installed()->values(),
            ],
            'jsPackageManagers' => [
                'configured' => $this->js->packageManagers()->configured()->values(),
                'installed' => $this->js->packageManagers()->installed()->values(),
            ],
        ];

        return json_encode($payload, JSON_PRETTY_PRINT) ?: '{}';
    }
}
