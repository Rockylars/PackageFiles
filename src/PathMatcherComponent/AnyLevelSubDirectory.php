<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\PathMatcherComponent;

use Rocky\PackageFiles\PathMatcher;
use Rocky\PackageFiles\PathMatcherComponent;

final class AnyLevelSubDirectory implements PathMatcherComponent
{
    /** @inheritDoc */
    public function asRegExp(): string
    {
        // (?:\/|\/.+\/)
        return '(?:' . self::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR . '|' . self::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR . '.+' . self::REG_EXP_ESCAPE . PathMatcher::DIRECTORY_SEPARATOR  . ')';
    }
}
