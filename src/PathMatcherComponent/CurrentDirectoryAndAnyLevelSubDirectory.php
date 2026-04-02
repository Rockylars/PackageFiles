<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\PathMatcherComponent;

use Rocky\PackageFiles\PathMatcher;

final class CurrentDirectoryAndAnyLevelSubDirectory implements PathMatcherComponentInterface
{
    public function __construct(
        private bool $currentDirectoryIsRoot
    ) {}

    /** @inheritDoc */
    public function asRegExp(): string
    {
        // We start the paths without a slash.
        if ($this->currentDirectoryIsRoot) {
            // (?:^|\/|\/.+\/)
            return '(?:^|' . PathMatcher::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR . '|' . PathMatcher::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR . '.+' . PathMatcher::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR . ')';
        } else {
            // (?:\/|\/.+\/)
            return '(?:' . PathMatcher::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR . '|' . PathMatcher::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR . '.+' . PathMatcher::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR . ')';
        }
    }
}
