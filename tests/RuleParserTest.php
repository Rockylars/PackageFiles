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
        self::assertSame(
            '(?:^|^.+\/)something$',
            RuleParser::run('something           ', false)?->asRegExp()
        );
        self::assertSame(
            null,
            RuleParser::run('something           ', true)?->asRegExp()
        );
        self::assertSame(
            '(?:^|^.+\/)something export-ignore$',
            RuleParser::run('something export-ignore          ', false)?->asRegExp()
        );
        self::assertSame(
            '(?:^|^.+\/)something$',
            RuleParser::run('something export-ignore          ', true)?->asRegExp()
        );
    }
}