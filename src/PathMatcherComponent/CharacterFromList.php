<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\PathMatcherComponent;

use Rocky\PackageFiles\PathMatcherComponent;

final class CharacterFromList implements PathMatcherComponent
{
    // Array of components
    // is excluding

    /** @inheritDoc */
    public function asRegExp(): string
    {
        // [afdf]
        // [a-z]
        // [^afdf]
        // [^a-z]
        return '';
    }
}
