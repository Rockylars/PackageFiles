<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\PathMatcherComponent;

final class Anything implements PathMatcherComponentInterface
{
    /** @inheritDoc */
    public function asRegExp(): string
    {
        // .*
        return '.*';
    }
}
