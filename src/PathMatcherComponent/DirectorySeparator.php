<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\PathMatcherComponent;

use Rocky\PackageFiles\PathMatcher;

final class DirectorySeparator implements PathMatcherComponentInterface
{
    /** @inheritDoc */
    public function asRegExp(): string
    {
        // \/
        return PathMatcher::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR;
    }
}
