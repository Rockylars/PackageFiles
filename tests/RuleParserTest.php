<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests;

use PHPUnit\Framework\TestCase;
use Rocky\PackageFiles\PathMatcher;
use Rocky\PackageFiles\RuleParser;

final class RuleParserTest extends TestCase
{
    /** @test */
    public static function run_will_create_a_matcher_for_the_given_git_ignore_line(): void
    {
        $result = RuleParser::run('ign?re/me/something!', false);
        self::assertNotNull($result);

        /** @var PathMatcher $result */
        self::assertSame($regExp = '^ign[^\/]re\/me\/something!$', $result->asRegExp());
        self::assertMatchesRegularExpression('/' . $regExp . '/u', 'ignore/me/something!');
    }

    /** @test */
    public static function run_will_create_a_matcher_for_the_given_git_attributes_line(): void
    {
        $result = RuleParser::run('ignore/me/an?ther-thing\? export-ignore', true);
        self::assertNotNull($result);

        /** @var PathMatcher $result */
        self::assertSame($regExp = '^ignore\/me\/an[^\/]ther-thing\?$', $result->asRegExp());
        self::assertMatchesRegularExpression('/' . $regExp . '/u', 'ignore/me/another-thing?');
    }
}