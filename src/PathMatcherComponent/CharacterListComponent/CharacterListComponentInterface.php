<?php

namespace Rocky\PackageFiles\PathMatcherComponent\CharacterListComponent;

interface CharacterListComponentInterface
{
    /** @return non-empty-string */
    public function asRegExp(): string;
}
