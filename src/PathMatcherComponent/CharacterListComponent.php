<?php

namespace Rocky\PackageFiles\PathMatcherComponent;

interface CharacterListComponent
{
    /** @return non-empty-string */
    public function asRegExp(): string;
}
