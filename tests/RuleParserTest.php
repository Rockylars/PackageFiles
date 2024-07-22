<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests;

use PHPUnit\Framework\TestCase;
use Rocky\PackageFiles\PackageParser;
use Rocky\PackageFiles\RuleParser;

final class RuleParserTest extends TestCase
{
    /** @test */
    public static function run_will_strip_trailing_whitespace(): void
    {
        RuleParser::run('something           ', false);
    }
}