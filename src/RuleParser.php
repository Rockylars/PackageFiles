<?php

declare(strict_types=1);

namespace Rocky\PackageFiles;

use Exception;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\PcreException;

final class RuleParser
{
    public static function run(string $rule): void
    {
        $text = trim($rule);
        if (str_starts_with($text, '#')) {
            return;
        }
    }
}
