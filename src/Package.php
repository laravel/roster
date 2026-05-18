<?php

namespace Laravel\Roster;

use Laravel\Roster\Enums\PackageSource;

class Package
{
    public function __construct(
        protected string $name,
        protected string $version,
        protected PackageSource $source,
        protected ?string $alias = null,
        protected bool $dev = false,
        protected bool $direct = false,
        protected string $constraint = '',
        protected ?string $path = null,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function alias(): ?string
    {
        return $this->alias;
    }

    public function withAlias(?string $alias): self
    {
        $clone = clone $this;
        $clone->alias = $alias;

        return $clone;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function major(): int
    {
        $parts = explode('.', $this->version);

        return (int) $parts[0];
    }

    public function isDev(): bool
    {
        return $this->dev;
    }

    public function isDirect(): bool
    {
        return $this->direct;
    }

    public function constraint(): string
    {
        return $this->constraint;
    }

    public function source(): PackageSource
    {
        return $this->source;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function matches(string $query): bool
    {
        return $this->name === $query || $this->alias === $query;
    }

    /**
     * @return array{name: string, alias: ?string, version: string, constraint: string, direct: bool, dev: bool, source: string, path: ?string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'alias' => $this->alias,
            'version' => $this->version,
            'constraint' => $this->constraint,
            'direct' => $this->direct,
            'dev' => $this->dev,
            'source' => $this->source->value,
            'path' => $this->path,
        ];
    }
}
