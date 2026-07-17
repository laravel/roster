<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Illuminate\Support\Str;
use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Detectors\ApproachDetector;
use Laravel\Roster\Detectors\BrowserTestFrameworkDetector;
use Laravel\Roster\Detectors\EditorsDetector;
use Laravel\Roster\Detectors\FrontendDetector;
use Laravel\Roster\Detectors\StackDetector;
use Laravel\Roster\Ecosystems\Ecosystem;
use Laravel\Roster\Ecosystems\JsEcosystem;
use Laravel\Roster\Enums\Agent;
use Laravel\Roster\Enums\BrowserTestFramework;
use Laravel\Roster\Enums\Editor;
use Laravel\Roster\Enums\Frontend;
use Laravel\Roster\Enums\Stack;
use Laravel\Roster\Scanners\ComposerLock;
use Laravel\Roster\Scanners\JsLockfile;
use Laravel\Roster\Support\ApproachSet;
use Laravel\Roster\Support\EnumSet;
use Throwable;

class ProjectScan
{
    protected ?ApproachSet $approaches = null;

    /**
     * @param  EnumSet<Stack>  $stacks
     * @param  EnumSet<BrowserTestFramework>  $browserTestFrameworks
     * @param  EnumSet<Frontend>  $frontends
     * @param  EnumSet<Agent>  $agents
     * @param  EnumSet<Editor>  $editors
     */
    public function __construct(
        protected string $basePath,
        protected Ecosystem $php,
        protected JsEcosystem $js,
        protected EnumSet $stacks,
        protected EnumSet $browserTestFrameworks,
        protected EnumSet $frontends,
        protected EnumSet $agents,
        protected EnumSet $editors,
    ) {
        //
    }

    public function php(): Ecosystem
    {
        return $this->php;
    }

    public function js(): JsEcosystem
    {
        return $this->js;
    }

    /** @return EnumSet<Stack> */
    public function stacks(): EnumSet
    {
        return $this->stacks;
    }

    /** @return EnumSet<BrowserTestFramework> */
    public function browserTestFrameworks(): EnumSet
    {
        return $this->browserTestFrameworks;
    }

    /** @return EnumSet<Frontend> */
    public function frontends(): EnumSet
    {
        return $this->frontends;
    }

    /** @return EnumSet<Agent> */
    public function agents(): EnumSet
    {
        return $this->agents;
    }

    /** @return EnumSet<Editor> */
    public function editors(): EnumSet
    {
        return $this->editors;
    }

    public function approaches(): ApproachSet
    {
        return $this->approaches ??= new ApproachSet(
            ApproachDetector::detect($this->basePath),
        );
    }

    public static function scan(?string $basePath = null): self
    {
        $basePath = self::normalizeBasePath($basePath);

        $phpPackages = (new ComposerLock($basePath))->scan();

        $jsLockfile = new JsLockfile($basePath);
        $jsPackages = $jsLockfile->scan();

        $php = new Ecosystem($phpPackages);
        $js = new JsEcosystem($jsPackages, $jsLockfile->committedManager());

        return new self(
            $basePath,
            $php,
            $js,
            new EnumSet(StackDetector::detect($php, $js)),
            new EnumSet(BrowserTestFrameworkDetector::detect($php, $js, $basePath)),
            new EnumSet(FrontendDetector::detect($js)),
            new EnumSet(AgentsDetector::detect($basePath)),
            new EnumSet(EditorsDetector::detect($basePath)),
        );
    }

    /**
     * @internal
     */
    public static function normalizeBasePath(?string $basePath): string
    {
        return Str::finish($basePath ?? self::defaultBasePath(), DIRECTORY_SEPARATOR);
    }

    private static function defaultBasePath(): string
    {
        if (function_exists('base_path')) {
            try {
                return base_path();
            } catch (Throwable) {
                //
            }
        }

        return getcwd() ?: '.';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'php' => array_map(fn (Package $package): array => $package->toArray(), $this->php->packages()->all()),
            'js' => array_map(fn (Package $package): array => $package->toArray(), $this->js->packages()->all()),
            'stacks' => $this->stacks->values(),
            'browserTestFrameworks' => $this->browserTestFrameworks->values(),
            'frontends' => $this->frontends->values(),
            'agents' => $this->agents->values(),
            'editors' => $this->editors->values(),
            'jsPackageManager' => $this->js->packageManager()?->value,
        ];
    }

    public function json(): string
    {
        return self::encode($this->toArray());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function encode(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION) ?: '{}';
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $properties = get_object_vars($this);
        unset($properties['approaches']);

        return $properties;
    }

    /**
     * @param  array{
     *     basePath: string,
     *     php: Ecosystem,
     *     js: JsEcosystem,
     *     stacks: EnumSet<Stack>,
     *     browserTestFrameworks: EnumSet<BrowserTestFramework>,
     *     frontends: EnumSet<Frontend>,
     *     agents: EnumSet<Agent>,
     *     editors: EnumSet<Editor>,
     * }  $properties
     */
    public function __unserialize(array $properties): void
    {
        foreach ($properties as $property => $value) {
            $this->{$property} = $value;
        }

        $this->approaches = null;
    }
}
