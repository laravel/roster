<?php

namespace Laravel\Roster;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
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

        $resolved = $basePath ?? (function_exists('base_path') ? base_path() : (getcwd() ?: '.'));
        $basePath = rtrim($resolved, DIRECTORY_SEPARATOR.'/').DIRECTORY_SEPARATOR;

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

    /**
     * Resolve the Registry from the container when available; otherwise return
     * a fresh one. A binding failure is the expected "not booted" path; any
     * other exception is the user's misconfiguration and should surface.
     */
    private static function resolveRegistry(): Registry
    {
        if (! Container::getInstance()->bound(Registry::class)) {
            return new Registry;
        }

        try {
            /** @var Registry */
            return Container::getInstance()->make(Registry::class);
        } catch (BindingResolutionException) {
            return new Registry;
        }
    }

    public function json(): string
    {
        $payload = [
            'php' => $this->php->packages()->map(fn (Package $p) => $p->toArray())->all(),
            'js' => $this->js->packages()->map(fn (Package $p) => $p->toArray())->all(),
            'stack' => self::enumValues($this->stack->all()),
            'testFramework' => $this->testFramework?->value,
            'browserTestFrameworks' => self::enumValues($this->browserTestFrameworks->all()),
            'frontend' => self::enumValues($this->frontend->all()),
            'starterKit' => self::enumValues($this->starterKit->all()),
            'approach' => self::enumValues($this->approach->all()),
            'agents' => [
                'configured' => self::enumValues($this->agents->configured()->all()),
                'installed' => self::enumValues($this->agents->installed()->all()),
            ],
            'jsPackageManagers' => [
                'configured' => self::enumValues($this->js->packageManagers()->configured()->all()),
                'installed' => self::enumValues($this->js->packageManagers()->installed()->all()),
            ],
        ];

        return json_encode($payload, JSON_PRETTY_PRINT) ?: '{}';
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<int, string|int>
     */
    private static function enumValues(array $cases): array
    {
        return array_map(fn (\BackedEnum $c) => $c->value, $cases);
    }
}
