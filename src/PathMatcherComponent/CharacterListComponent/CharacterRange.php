<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\PathMatcherComponent\CharacterListComponent;

final class CharacterRange implements CharacterListComponentInterface
{
    /** @inheritDoc */
    public function asRegExp(): string
    {
        // a-z
        return '';
    }
}
