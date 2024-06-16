<?php

namespace Rocky\PackageFiles;

interface PathMatcherComponent
{
    public const REG_EXP_ESCAPE = '\\';

    /** @return non-empty-string */
    public function asRegExp(): string;
}
