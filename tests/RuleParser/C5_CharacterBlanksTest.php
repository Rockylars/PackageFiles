<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests\RuleParser;

use PHPUnit\Framework\TestCase;
use Rocky\PackageFiles\PackageParser;
use Rocky\PackageFiles\RuleParser;

final class C5_CharacterBlanksTest extends TestCase
{
    /** @test */
    public static function pattern_5A_star_can_match_no_characters_and_many_but_not_directory_separator(): void
    {
        self::assertSame(
            $pattern = '(?:^|^.+\/)magic[^\/]*thing$',
            RuleParser::run('magic*thing', false)?->asRegExp()
        );
        self::assertSame(
            $pattern,
            RuleParser::run('magic*thing export-ignore', true)?->asRegExp()
        );

        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'magic thing');
        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'magic    thing');
        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'magical thing');
        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'magicthing');

        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'magic/thing');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'magic / thing');
    }

    /** @test */
    public static function pattern_5B_question_mark_can_match_one_but_not_none_or_many_and_not_directory_separator(): void
    {
        self::assertSame(
            $pattern = '(?:^|^.+\/)magic[^\/]thing$',
            RuleParser::run('magic?thing', false)?->asRegExp()
        );
        self::assertSame(
            $pattern,
            RuleParser::run('magic?thing export-ignore', true)?->asRegExp()
        );

        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'magic thing');
        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'magicathing');
        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'magic?thing');

        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'magicthing');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'magical thing');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'magic    thing');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'magicthing');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'magic/thing');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'magic / thing');
    }
}