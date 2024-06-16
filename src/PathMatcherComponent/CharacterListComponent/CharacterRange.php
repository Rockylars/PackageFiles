<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\PathMatcherComponent\CharacterListComponent;

use Rocky\PackageFiles\PathMatcherComponent;

final class CharacterRange implements PathMatcherComponent\CharacterListComponent
{
    /** @inheritDoc */
    public function asRegExp(): string
    {
        // a-z
        return '';
    }
}
