<?php

namespace Rocky\PackageFiles\PathMatcherComponent;

interface PathMatcherComponentInterface
{
    /** @return non-empty-string */
    public function asRegExp(): string;
}
