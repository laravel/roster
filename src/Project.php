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
use Laravel\Roster\Scanners\Composer;
use Laravel\Roster\Scanners\JsLockfile;
use Laravel\Roster\Support\ApproachSet;
use Laravel\Roster\Support\EnumSet;

class Project
{
    protected ?ApproachSet $approaches = null;

    /**
     * @param  EnumSet<Stack>  $stack
     * @param  EnumSet<BrowserTestFramework>  $browserTestFrameworks
     * @param  EnumSet<Frontend>  $frontend
     * @param  EnumSet<Agent>  $agents
     * @param  EnumSet<Editor>  $editors
     */
    public function __construct(
        protected string $basePath,
        protected Ecosystem $php,
        protected JsEcosystem $js,
        protected EnumSet $stack,
        protected EnumSet $browserTestFrameworks,
        protected EnumSet $frontend,
        protected EnumSet $agents,
        protected EnumSet $editors,
    ) {}

    public function php(): Ecosystem
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
        return $this->approaches ??= new ApproachSet(ApproachesDetector::detect($this->basePath));
    }

    public static function scan(?string $basePath = null): self
    {
        $basePath = self::normalizeBasePath($basePath);

        $phpPackages = (new Composer($basePath.'composer.lock'))->scan();

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
            new EnumSet(AgentsDetector::configured($basePath)),
            new EnumSet(EditorsDetector::configured($basePath)),
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
            'browserTestFrameworks' => $this->browserTestFrameworks->values(),
            'frontend' => $this->frontend->values(),
            'agents' => $this->agents->values(),
            'editors' => $this->editors->values(),
            'jsPackageManager' => $this->js->packageManager()?->value,
        ];
    }

    public function json(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT) ?: '{}';
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
     * @param  array{basePath: string, php: Ecosystem, js: JsEcosystem, stack: EnumSet<Stack>, browserTestFrameworks: EnumSet<BrowserTestFramework>, frontend: EnumSet<Frontend>, agents: EnumSet<Agent>, editors: EnumSet<Editor>}  $properties
     */
    public function __unserialize(array $properties): void
    {
        $this->basePath = $properties['basePath'];
        $this->php = $properties['php'];
        $this->js = $properties['js'];
        $this->stack = $properties['stack'];
        $this->browserTestFrameworks = $properties['browserTestFrameworks'];
        $this->frontend = $properties['frontend'];
        $this->agents = $properties['agents'];
        $this->editors = $properties['editors'];
        $this->approaches = null;
    }
}
