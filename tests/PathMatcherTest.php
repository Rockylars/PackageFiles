<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests;

use PHPUnit\Framework\TestCase;
use Rocky\PackageFiles\PathMatcher;
use Rocky\PackageFiles\PathMatcherComponent\AnyCharacterExceptDirectoryIndicator;
use Rocky\PackageFiles\PathMatcherComponent\Character;
use Rocky\PackageFiles\PathMatcherComponent\CurrentDirectoryAndAnyLevelSubDirectory;
use Rocky\PackageFiles\PathMatcherComponent\DirectorySeparator;

final class PathMatcherTest extends TestCase
{
    /** @test */
    public static function as_reg_exp_will_match_impossible_empty_folder_or_file_name_for_empty_setup(): void
    {
        $pathMatcher = new PathMatcher();

        self::assertSame($pattern = '(?:^|^.+\/)$', $pathMatcher->asRegExp());
        self::assertMatchesRegularExpression('/' . $pattern . '/u', '');
        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'something/');
        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'something/another/');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'test');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/test');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/another/test');

        $pathMatcher->setTargetsCheckingParentDirectories();

        self::assertSame($pattern = '^$', $pathMatcher->asRegExp());
        self::assertMatchesRegularExpression('/' . $pattern . '/u', '');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/another/');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'test');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/test');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/another/test');
    }

    /** @test */
    public static function as_reg_exp_will_match_a_few_patterns(): void
    {
        $pathMatcher = new PathMatcher();
        $pathMatcher->setTargetsCheckingParentDirectories();
        $pathMatcher->addPathComponent(new Character('a'));
        $pathMatcher->addPathComponent(new Character('b'));
        $pathMatcher->addPathComponent(new Character('c'));
        $pathMatcher->addPathComponent(new Character('('));
        $pathMatcher->addPathComponent(new Character('1'));
        $pathMatcher->addPathComponent(new Character(')'));
        $pathMatcher->addPathComponent(new DirectorySeparator());
        $pathMatcher->addPathComponent(new AnyCharacterExceptDirectoryIndicator());
        $pathMatcher->addPathComponent(new AnyCharacterExceptDirectoryIndicator());
        $pathMatcher->addPathComponent(new AnyCharacterExceptDirectoryIndicator());
        $pathMatcher->addPathComponent(new CurrentDirectoryAndAnyLevelSubDirectory(false));
        $pathMatcher->addPathComponent(new Character('x'));
        $pathMatcher->addPathComponent(new Character('y'));
        $pathMatcher->addPathComponent(new Character('z'));

        self::assertSame($pattern = '^abc\(1\)\/[^\/][^\/][^\/](?:\/|\/.+\/)xyz$', $pathMatcher->asRegExp());

        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', '');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/another/');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'test');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/test');
        self::assertDoesNotMatchRegularExpression('/' . $pattern . '/u', 'something/another/test');

        self::assertMatchesRegularExpression('/' . $pattern . '/u', 'abc(1)/klm/something/another/xyz');
    }
}