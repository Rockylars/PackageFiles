<?php

declare(strict_types=1);

namespace Rocky\PackageFiles;

use Rocky\PackageFiles\PathMatcherComponent\PathMatcherComponentInterface;

// included: ex_1f, ex_1d/, ex_2f, ex_2d/
// excluded: -
// rule: ignore "/ex_1?" -> exclude match
// included: ex_2f, ex_2d/
// excluded: ex_1f, ex_1d/

// included: ex_1f, ex_1d/, ex_2f, ex_2d/
// excluded: -
// rule: ignore "/ex_1?/" -> exclude match
// included: ex_1f, ex_2f, ex_2d/
// excluded: ex_1d/

// rule: ignore "/ex*" -> exclude match
// included: -
// excluded: ex_1f, ex_1d/, ex_2f, ex_2d/
// rule: ignore "!/ex_1?" -> include match
// included: ex_1f, ex_1d/
// excluded: ex_2f, ex_2d/

// rule: ignore "/ex*" -> exclude match
// included: -
// excluded: ex_1f, ex_1d/, ex_2f, ex_2d/
// rule: ignore "!/ex_1?/" -> include match
// included: ex_1d/
// excluded: ex_1f, ex_2f, ex_2d/
final class PathMatcher
{
    public const REG_EXP_ESCAPE = '\\';
    public const DIRECTORY_SEPARATOR = '/';

    /** @var array<int, PathMatcherComponentInterface> */
    private array $pathComponents = [];
    private bool $toInclude = false;

    private bool $targetsWithoutCheckingParentDirectories = true;
    private bool $targetsOnlyDirectories = false;

    public function asRegExp(): string
    {
        if ($this->targetsWithoutCheckingParentDirectories) {
            // (?:^|^.+\/)
            $regExp = '(?:^|^.+' . self::REG_EXP_ESCAPE . self::DIRECTORY_SEPARATOR . ')';
        } else {
            // ^
            $regExp = '^';
        }
        $pathComponentCount = count($this->pathComponents);
        for ($i = 0; $i < $pathComponentCount; $i++) {
            $regExp .= $this->pathComponents[$i]->asRegExp();
        }
        return $regExp . '$';
    }

    public function toInclude(): bool
    {
        return $this->toInclude;
    }

    public function targetsWithoutCheckingParentDirectories(): bool
    {
        return $this->targetsWithoutCheckingParentDirectories;
    }

    public function targetsOnlyDirectories(): bool
    {
        return $this->targetsOnlyDirectories;
    }

    public function addPathComponent(PathMatcherComponentInterface $pathMatcherComponent): void
    {
        $this->pathComponents[] = $pathMatcherComponent;
    }

    public function setToInclude(): void
    {
        $this->toInclude = true;
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
