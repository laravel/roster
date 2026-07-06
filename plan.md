## Add app-convention ("approaches") detection

Extend the new `Project` facade with an `approaches()` detector family that inspects
the project's **own source code** (not just manifests) and reports which stylistic
conventions the app has adopted. This is the reference implementation being lifted out
of laravel/boost, which will consume this API instead of scanning source itself.

### Public API (must match — Boost depends on this shape)

    Project::approaches()->uses(Approach::MASS_ASSIGNMENT_FILLABLE): bool   // is this the dominant style?
    Project::approaches()->all(): Collection<Approach, ApproachResult>      // all confidently-detected approaches

`all()` returns ONLY approaches that clear the confidence gate below.

### The `Approach` enum (one case per outcome, like Stack/Agent)

    MASS_ASSIGNMENT_FILLABLE, MASS_ASSIGNMENT_GUARDED,
    ENUM_CASE_SCREAMING_SNAKE, ENUM_CASE_PASCAL, ENUM_CASE_CAMEL,
    VALIDATION_PIPE_SYNTAX, VALIDATION_ARRAY_SYNTAX,
    QUERY_SCOPE_ATTRIBUTE, QUERY_SCOPE_METHOD

### The `ApproachResult` DTO (Boost needs every field)

    approach: Approach
    confidence: float     // raw winning ratio (votes / total) — Boost uses this for its preselect threshold
    matched: int          // winning votes    ┐ Boost phrases "detected in 9/10 models"
    total: int            // total votes       ┘
    paths: string[]       // absolute paths of the files that voted — Boost derives the rule's glob from these

### The four detectors to port (reference logic)

1. **Mass assignment** — scan `Models/`, one vote/model: `protected $fillable` vs `protected $guarded`.
   Skip models declaring both or neither.
2. **Enum case casing** — scan files containing `enum`, one vote **per enum case** (not per file).
   Use a TOKEN scan (track brace depth) so a `case Active:` inside a `switch` is NOT counted as an enum case.
   Classify each name as SCREAMING_SNAKE / Pascal / camel.
3. **Validation rule syntax** — scan `Http/Requests/` files with a `rules()` method, one vote/request:
   pipe strings `'required|max:255'` vs arrays `['required','max:255']` (dominant local notation wins).
4. **Query scope style** — scan `Models/`, one vote **per scope**: `#[Scope]` attribute vs `scopeXxx()` naming.

### Confidence gate (the important part — applies to every detector)

Do NOT report on a raw ratio. Require:
- at least **MIN_SAMPLE = 5** total votes, AND
- the **Wilson score lower bound** (95% CI, z = 1.96) of the winner's proportion ≥ **0.5**.

This is why 4/5 is rejected but 90/100 passes, and why a 50/50 split stays silent. Reference:

    center = (p̂ + z²/2n) / (1 + z²/n)
    margin = (z / (1 + z²/n)) * sqrt( p̂(1-p̂)/n + z²/4n² )
    wilsonLower = center - margin        // p̂ = winner votes / total, n = total

### Source discovery & sampling

- Resolve source roots from composer.json PSR-4 `autoload` ∪ `app/` (existing dirs only).
- When a detector targets a subpath (e.g. `Models`, `Http/Requests`), match it **anywhere beneath
  the root** (regex `#(^|/)Models/#`), so modular layouts like `src/Domain/Orders/Models/Order.php`
  are sampled — not just `<root>/Models/`.
- Dedupe files across overlapping roots by real path.

### Caching

The existing lockfile-hash cache is WRONG for this family — source changes without touching the
lockfile. Invalidate on source content/mtime instead (or don't cache approaches).

### Tests

Port Boost's fixtures: fillable-models-app, guarded-models-app, mixed-models-app (must stay silent —
split fails the Wilson gate), screaming-enums-app, enum-switch-app (switch `case` not counted),
pipe-validation-app, attribute-scopes-app, naming-scopes-app, nested-models-app (deep modular nesting),
carbon-app (too few models → silent).

Once this lands and Roster is released, the Boost follow-up is small: add a RosterApproachDetector implements Detector that calls Project::approaches()->all(), maps each Approach to a rule title/note, and derives the glob from paths via the old globForFiles logic — then register it in ConventionInspector alongside the two existing detectors.
