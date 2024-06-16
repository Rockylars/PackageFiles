<?php

declare(strict_types=1);

namespace Rocky\PackageFiles;

final class PathMatcher
{
    public const DIRECTORY_SEPARATOR = '/';

    /** @var array<int, PathMatcherComponent> */
    private array $pathComponents = [];
    private bool $targetsMatching = true;
    private bool $targetsWithoutCheckingParentDirectories = true;
    private bool $targetsOnlyDirectories = false;

    public function asRegExp(): string
    {
        $regExp = '';
        $pathComponentCount = count($this->pathComponents);
        for ($i = 0; $i < $pathComponentCount; $i++) {
            $regExp .= $this->pathComponents[$i]->asRegExp();
        }
        return $regExp;
    }

    public function targetsMatching(): bool
    {
        return $this->targetsMatching;
    }

    public function targetsWithoutCheckingParentDirectories(): bool
    {
        return $this->targetsWithoutCheckingParentDirectories;
    }

    public function targetsOnlyDirectories(): bool
    {
        return $this->targetsOnlyDirectories;
    }

    public function addPathComponent(PathMatcherComponent $pathMatcherComponent): void
    {
        $this->pathComponents[] = $pathMatcherComponent;
    }

    public function setTargetsNotMatching(): void
    {
        $this->targetsMatching = false;
    }

    public function setTargetsCheckingParentDirectories(): void
    {
        $this->targetsWithoutCheckingParentDirectories = false;
    }

    public function setTargetsOnlyDirectories(): void
    {
        $this->targetsOnlyDirectories = true;
    }
}
