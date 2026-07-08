<?php

declare(strict_types=1);

use Laravel\Roster\ApproachResult;
use Laravel\Roster\Detectors\ApproachesDetector;
use Laravel\Roster\Enums\Approach;
use Laravel\Roster\Support\ApproachSet;

function detectApproaches(string $app): ApproachSet
{
    return new ApproachSet(ApproachesDetector::detect(
        dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'approaches'.DIRECTORY_SEPARATOR.$app,
    ));
}

function writeModel(string $base, string $name, string $property): void
{
    touchFile($base.'app/Models/'.$name.'.php');
    file_put_contents(
        $base.'app/Models/'.$name.'.php',
        "<?php\n\nnamespace App\\Models;\n\nclass {$name}\n{\n    protected \${$property} = [];\n}\n",
    );
}

it('detects fillable as the dominant mass-assignment style', function (): void {
    $approaches = detectApproaches('fillable-models-app');

    expect($approaches->uses(Approach::MassAssignmentFillable))->toBeTrue()
        ->and($approaches->uses(Approach::MassAssignmentGuarded))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MassAssignmentFillable->value);

    expect($result->matched)->toBe(6)
        ->and($result->total)->toBe(6)
        ->and($result->confidence)->toBe(1.0)
        ->and($result->paths)->toHaveCount(6)
        ->and($result->paths[0])->toContain('Models');
});

it('detects guarded as the dominant mass-assignment style', function (): void {
    $approaches = detectApproaches('guarded-models-app');

    expect($approaches->uses(Approach::MassAssignmentGuarded))->toBeTrue()
        ->and($approaches->uses(Approach::MassAssignmentFillable))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MassAssignmentGuarded->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('stays silent when models are split between fillable and guarded', function (): void {
    $approaches = detectApproaches('mixed-models-app');

    expect($approaches->uses(Approach::MassAssignmentFillable))->toBeFalse()
        ->and($approaches->uses(Approach::MassAssignmentGuarded))->toBeFalse();
});

it('detects screaming snake enum case naming with one vote per case', function (): void {
    $approaches = detectApproaches('screaming-enums-app');

    expect($approaches->uses(Approach::EnumCaseScreamingSnake))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::EnumCaseScreamingSnake->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5)
        ->and($result->paths)->toHaveCount(2);
});

it('does not count switch case labels as enum cases', function (): void {
    $approaches = detectApproaches('enum-switch-app');

    expect($approaches->uses(Approach::EnumCaseScreamingSnake))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::EnumCaseScreamingSnake->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects pascal enum case naming', function (): void {
    $approaches = detectApproaches('pascal-enums-app');

    expect($approaches->uses(Approach::EnumCasePascal))->toBeTrue()
        ->and($approaches->uses(Approach::EnumCaseScreamingSnake))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::EnumCasePascal->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects camel enum case naming', function (): void {
    $approaches = detectApproaches('camel-enums-app');

    expect($approaches->uses(Approach::EnumCaseCamel))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::EnumCaseCamel->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('counts enum cases declared after a method with string interpolation', function (): void {
    $approaches = detectApproaches('interpolated-enums-app');

    expect($approaches->uses(Approach::EnumCaseScreamingSnake))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::EnumCaseScreamingSnake->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it('detects pipe-delimited validation rule syntax', function (): void {
    $approaches = detectApproaches('pipe-validation-app');

    expect($approaches->uses(Approach::ValidationPipeSyntax))->toBeTrue()
        ->and($approaches->uses(Approach::ValidationArraySyntax))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ValidationPipeSyntax->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5)
        ->and($result->paths[0])->toContain('Requests');
});

it('detects array validation rule syntax', function (): void {
    $approaches = detectApproaches('array-validation-app');

    expect($approaches->uses(Approach::ValidationArraySyntax))->toBeTrue()
        ->and($approaches->uses(Approach::ValidationPipeSyntax))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ValidationArraySyntax->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);
});

it("does not count a regex rule's alternation as pipe validation syntax", function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Http/Requests/{$name}Request.php", <<<PHP
        class {$name}Request
        {
            public function rules(): array
            {
                return [
                    'code' => 'regex:/^(A|B)\$/',
                ];
            }
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::ValidationPipeSyntax))->toBeFalse();
});

it('samples Models directories anywhere beneath a PSR-4 root', function (): void {
    $approaches = detectApproaches('nested-models-app');

    expect($approaches->uses(Approach::MassAssignmentFillable))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MassAssignmentFillable->value);

    expect($result->matched)->toBe(5)
        ->and($result->paths[0])->toContain('src/Domain/Orders/Models');
});

it('stays silent when there are too few votes', function (): void {
    expect(detectApproaches('carbon-app')->all())->toBeEmpty();
});

it('reports a convention once the minimum sample of three is reached', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie'] as $name) {
        writeModel($base, $name, 'fillable');
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::MassAssignmentFillable))->toBeTrue();

    cleanup($base);
});

it('rejects a 4/5 majority as insufficient evidence', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
        writeModel($base, $name, 'fillable');
    }

    writeModel($base, 'Hotel', 'guarded');

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::MassAssignmentFillable))->toBeFalse()
        ->and($approaches->uses(Approach::MassAssignmentGuarded))->toBeFalse();

    cleanup($base);
});

it('accepts a 90/100 majority', function (): void {
    $base = tempBase();

    for ($i = 1; $i <= 90; $i++) {
        writeModel($base, 'Fillable'.$i, 'fillable');
    }

    for ($i = 1; $i <= 10; $i++) {
        writeModel($base, 'Guarded'.$i, 'guarded');
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::MassAssignmentFillable))->toBeTrue();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MassAssignmentFillable->value);

    expect($result->matched)->toBe(90)
        ->and($result->total)->toBe(100)
        ->and($result->confidence)->toBe(0.9)
        ->and($result->paths)->toHaveCount(100);

    cleanup($base);
});

it('skips models declaring both fillable and guarded', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeModel($base, $name, 'fillable');
    }

    touchFile($base.'app/Models/Both.php');
    file_put_contents(
        $base.'app/Models/Both.php',
        "<?php\n\nnamespace App\\Models;\n\nclass Both\n{\n    protected \$fillable = [];\n\n    protected \$guarded = [];\n}\n",
    );

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MassAssignmentFillable->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5)
        ->and($result->paths)->toHaveCount(5);

    cleanup($base);
});

it('never lets vendor or node_modules code vote', function (): void {
    $base = tempBase();

    file_put_contents($base.'composer.json', json_encode([
        'autoload' => ['psr-4' => ['App\\' => '.']],
    ]));

    foreach (['vendor/acme/pkg/src', 'node_modules/pkg'] as $dir) {
        for ($i = 1; $i <= 5; $i++) {
            touchFile($base.$dir.'/Models/Dep'.$i.'.php');
            file_put_contents(
                $base.$dir.'/Models/Dep'.$i.'.php',
                "<?php\n\nclass Dep{$i}\n{\n    protected \$guarded = [];\n}\n",
            );
        }
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->all())->toBeEmpty();

    cleanup($base);
});

it('accepts an array of approaches with any-of semantics', function (): void {
    $approaches = detectApproaches('fillable-models-app');

    expect($approaches->uses([Approach::MassAssignmentFillable, Approach::MassAssignmentGuarded]))->toBeTrue()
        ->and($approaches->uses([Approach::MassAssignmentGuarded, Approach::EnumCaseCamel]))->toBeFalse();
});

it('dedupes files reachable through overlapping source roots', function (): void {
    $base = tempBase();

    file_put_contents($base.'composer.json', json_encode([
        'autoload' => ['psr-4' => ['App\\' => 'app/', 'App\\Models\\' => 'app/Models/']],
    ]));

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeModel($base, $name, 'fillable');
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MassAssignmentFillable->value);

    expect($result->total)->toBe(5);

    cleanup($base);
});

function writeAttributeModel(string $base, string $name, string $attribute): void
{
    touchFile($base.'app/Models/'.$name.'.php');
    file_put_contents(
        $base.'app/Models/'.$name.'.php',
        "<?php\n\nnamespace App\\Models;\n\nuse Illuminate\\Database\\Eloquent\\Attributes\\{$attribute};\n\n#[{$attribute}(['name'])]\nclass {$name}\n{\n}\n",
    );
}

it('counts #[Fillable] and #[Guarded] attributes as mass-assignment votes', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeAttributeModel($base, $name, 'Fillable');
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::MassAssignmentFillable))->toBeTrue()
        ->and($approaches->uses(Approach::MassAssignmentGuarded))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::MassAssignmentFillable->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);

    cleanup($base);
});

it('lets attribute-style models outvote legacy property-style models', function (): void {
    $base = tempBase();

    foreach (['Legacy1', 'Legacy2'] as $name) {
        writeModel($base, $name, 'guarded');
    }

    foreach (['New1', 'New2', 'New3', 'New4', 'New5', 'New6', 'New7', 'New8', 'New9', 'New10', 'New11'] as $name) {
        writeAttributeModel($base, $name, 'Fillable');
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::MassAssignmentFillable))->toBeTrue()
        ->and($approaches->uses(Approach::MassAssignmentGuarded))->toBeFalse();

    cleanup($base);
});

it('detects form requests as the dominant validation style', function (): void {
    $approaches = detectApproaches('pipe-validation-app');

    expect($approaches->uses(Approach::ValidationFormRequest))->toBeTrue()
        ->and($approaches->uses(Approach::ValidationInline))->toBeFalse();
});

it('detects inline validation as the dominant validation style', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        touchFile($base.'app/Http/Controllers/'.$name.'Controller.php');
        file_put_contents(
            $base.'app/Http/Controllers/'.$name.'Controller.php',
            "<?php\n\nnamespace App\\Http\\Controllers;\n\nclass {$name}Controller\n{\n    public function store(\$request)\n    {\n        \$request->validate(['name' => 'required']);\n    }\n}\n",
        );
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::ValidationInline))->toBeTrue()
        ->and($approaches->uses(Approach::ValidationFormRequest))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ValidationInline->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(5);

    cleanup($base);
});

it('stays silent when validation is split between inline and form requests', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie'] as $name) {
        touchFile($base.'app/Http/Requests/'.$name.'Request.php');
        file_put_contents(
            $base.'app/Http/Requests/'.$name.'Request.php',
            "<?php\n\nnamespace App\\Http\\Requests;\n\nclass {$name}Request\n{\n    public function rules(): array\n    {\n        return [];\n    }\n}\n",
        );
    }

    foreach (['Delta', 'Hotel'] as $name) {
        touchFile($base.'app/Http/Controllers/'.$name.'Controller.php');
        file_put_contents(
            $base.'app/Http/Controllers/'.$name.'Controller.php',
            "<?php\n\nnamespace App\\Http\\Controllers;\n\nclass {$name}Controller\n{\n    public function store(\$request)\n    {\n        \$request->validate(['name' => 'required']);\n    }\n}\n",
        );
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::ValidationFormRequest))->toBeFalse()
        ->and($approaches->uses(Approach::ValidationInline))->toBeFalse();

    cleanup($base);
});

function writeSource(string $base, string $relative, string $body): void
{
    touchFile($base.$relative);
    file_put_contents($base.$relative, "<?php\n\n".$body."\n");
}

it('detects invokable controllers as the dominant style', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Http/Controllers/{$name}Controller.php", <<<PHP
        class {$name}Controller
        {
            public function __invoke(): string
            {
                return 'ok';
            }
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::ControllerInvokable))->toBeTrue()
        ->and($approaches->uses(Approach::ControllerResourceful))->toBeFalse();

    cleanup($base);
});

it('detects resourceful controllers and lets non-resource controllers abstain', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Http/Controllers/{$name}Controller.php", <<<PHP
        class {$name}Controller
        {
            public function index(): string
            {
                return 'list';
            }

            public function store(): string
            {
                return 'created';
            }
        }
        PHP);
    }

    writeSource($base, 'app/Http/Controllers/SingleController.php', <<<'PHP'
    class SingleController
    {
        public function index(): string
        {
            return 'list';
        }
    }
    PHP);

    writeSource($base, 'app/Http/Controllers/HelperController.php', <<<'PHP'
    class HelperController
    {
        public function process(): string
        {
            return 'done';
        }

        public function middleware(): array
        {
            return [];
        }
    }
    PHP);

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::ControllerResourceful))->toBeTrue()
        ->and($approaches->uses(Approach::ControllerInvokable))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ControllerResourceful->value);

    expect($result->total)->toBe(5);

    cleanup($base);
});

it('detects command signature attribute vs property syntax', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Console/Commands/{$name}Command.php", <<<PHP
        #[Signature('{$name}:run')]
        #[Description('Run {$name}')]
        class {$name}Command
        {
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::CommandAttributeSyntax))->toBeTrue()
        ->and($approaches->uses(Approach::CommandPropertySyntax))->toBeFalse();

    cleanup($base);
});

it('detects command signature properties', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Console/Commands/{$name}Command.php", <<<PHP
        class {$name}Command
        {
            protected \$signature = '{$name}:run';

            protected \$description = 'Run {$name}';
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::CommandPropertySyntax))->toBeTrue()
        ->and($approaches->uses(Approach::CommandAttributeSyntax))->toBeFalse();

    cleanup($base);
});

it('detects http client throw style only in files using the Http client', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Services/{$name}.php", <<<PHP
        class {$name}
        {
            public function handle(): void
            {
                Http::get('https://example.com')->throw();
            }
        }
        PHP);
    }

    writeSource($base, 'app/Services/NotHttp.php', <<<'PHP'
    class NotHttp
    {
        public function handle($upload): void
        {
            $upload->failed();
            $upload->successful();
        }
    }
    PHP);

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::HttpClientThrow))->toBeTrue()
        ->and($approaches->uses(Approach::HttpClientStatusCheck))->toBeFalse();

    cleanup($base);
});

it('detects http client status-check style', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Services/{$name}.php", <<<PHP
        class {$name}
        {
            public function handle(): bool
            {
                \$response = Http::get('https://example.com');

                return \$response->successful() && ! \$response->failed();
            }
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::HttpClientStatusCheck))->toBeTrue()
        ->and($approaches->uses(Approach::HttpClientThrow))->toBeFalse();

    cleanup($base);
});

it('detects the notify trait style over the Notification facade', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Actions/{$name}.php", <<<PHP
        class {$name}
        {
            public function handle(\$user): void
            {
                \$user->notify(new InvoicePaid());
            }
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::NotificationNotify))->toBeTrue()
        ->and($approaches->uses(Approach::NotificationFacade))->toBeFalse();

    cleanup($base);
});

it('detects the Notification facade style', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Actions/{$name}.php", <<<PHP
        class {$name}
        {
            public function handle(\$users): void
            {
                Notification::send(\$users, new InvoicePaid());
            }
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::NotificationFacade))->toBeTrue()
        ->and($approaches->uses(Approach::NotificationNotify))->toBeFalse();

    cleanup($base);
});

it('detects the dominant authorization call style in controllers', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Http/Controllers/{$name}Controller.php", <<<PHP
        class {$name}Controller
        {
            public function update(\$request, \$post): void
            {
                Gate::authorize('update', \$post);
            }
        }
        PHP);
    }

    writeSource($base, 'app/Http/Controllers/LegacyController.php', <<<'PHP'
    class LegacyController
    {
        public function update($request, $post): void
        {
            $this->authorize('update', $post);
        }
    }
    PHP);

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::AuthorizationGate))->toBeTrue()
        ->and($approaches->uses(Approach::AuthorizationTrait))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::AuthorizationGate->value);

    expect($result->matched)->toBe(5)
        ->and($result->total)->toBe(6);

    cleanup($base);
});

it('detects the dominant auth user retrieval style', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Http/Controllers/{$name}Controller.php", <<<PHP
        class {$name}Controller
        {
            public function show(\$request): mixed
            {
                return \$request->user();
            }
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::AuthRequest))->toBeTrue()
        ->and($approaches->uses(Approach::AuthFacade))->toBeFalse()
        ->and($approaches->uses(Approach::AuthHelper))->toBeFalse();

    cleanup($base);
});

it('detects the auth facade and helper retrieval styles', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Services/{$name}.php", <<<PHP
        class {$name}
        {
            public function handle(): mixed
            {
                return Auth::user() ?? Auth::id();
            }
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::AuthFacade))->toBeTrue()
        ->and($approaches->uses(Approach::AuthHelper))->toBeFalse();

    cleanup($base);
});

it('detects uuid model keys and abstains on files mixing both traits', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Models/{$name}.php", <<<PHP
        class {$name}
        {
            use HasUuids;
        }
        PHP);
    }

    writeSource($base, 'app/Models/Mixed.php', <<<'PHP'
    class Mixed
    {
        use HasUuids;
        use HasUlids;
    }
    PHP);

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::ModelUuidKeys))->toBeTrue()
        ->and($approaches->uses(Approach::ModelUlidKeys))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ModelUuidKeys->value);

    expect($result->total)->toBe(5);

    cleanup($base);
});

it('detects ulid model keys', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel'] as $name) {
        writeSource($base, "app/Models/{$name}.php", <<<PHP
        class {$name}
        {
            use HasUlids;
        }
        PHP);
    }

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::ModelUlidKeys))->toBeTrue()
        ->and($approaches->uses(Approach::ModelUuidKeys))->toBeFalse();

    cleanup($base);
});

it('does not let a call-heavy inline file or form request internals dilute the validation style', function (): void {
    $base = tempBase();

    foreach (['Alpha', 'Bravo', 'Charlie', 'Delta', 'Hotel', 'India'] as $name) {
        $extra = $name === 'Alpha' ? "\n        \$v = Validator::make([], []);\n        \$w = Validator::make([], []);" : '';
        writeSource($base, "app/Http/Requests/{$name}Request.php", <<<PHP
        class {$name}Request
        {
            public function rules(): array
            {{$extra}
                return [];
            }
        }
        PHP);
    }

    writeSource($base, 'app/Http/Controllers/BusyController.php', <<<'PHP'
    class BusyController
    {
        public function store($request): void
        {
            $request->validate(['a' => 'required']);
            $request->validate(['b' => 'required']);
            $request->validate(['c' => 'required']);
        }
    }
    PHP);

    $approaches = new ApproachSet(ApproachesDetector::detect($base));

    expect($approaches->uses(Approach::ValidationFormRequest))->toBeTrue()
        ->and($approaches->uses(Approach::ValidationInline))->toBeFalse();

    /** @var ApproachResult $result */
    $result = $approaches->all()->get(Approach::ValidationFormRequest->value);

    expect($result->matched)->toBe(6)
        ->and($result->total)->toBe(7)
        ->and(count($result->paths))->toBe(count(array_unique($result->paths)));

    cleanup($base);
});
