<?php

declare(strict_types=1);

namespace Laravel\Roster;

use Illuminate\Support\Str;
use Laravel\Roster\Detectors\AgentsDetector;
use Laravel\Roster\Detectors\ApproachesDetector;
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

class Project
{
    protected ?ApproachSet $approaches = null;

    protected int $approachesGeneration = 0;

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
        $generation = ApproachesDetector::generation();

        if (! $this->approaches instanceof ApproachSet || $this->approachesGeneration !== $generation) {
            $this->approaches = new ApproachSet(ApproachesDetector::detect($this->basePath));
            $this->approachesGeneration = $generation;
        }

        return $this->approaches;
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
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}';
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        $properties = get_object_vars($this);
        unset($properties['approaches'], $properties['approachesGeneration']);

        return $properties;
    }

    /**
     * @param  array{basePath: string, php: Ecosystem, js: JsEcosystem, stacks: EnumSet<Stack>, browserTestFrameworks: EnumSet<BrowserTestFramework>, frontends: EnumSet<Frontend>, agents: EnumSet<Agent>, editors: EnumSet<Editor>}  $properties
     */
    public function __unserialize(array $properties): void
    {
        foreach ($properties as $property => $value) {
            $this->{$property} = $value;
        }

        $this->approaches = null;
    }
}
