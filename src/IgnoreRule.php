<?php

declare(strict_types=1);

namespace Rocky\PackageFiles;

final class IgnoreRule
{
    public function __construct(
        public bool $bringFilesBack
    ) {}
}