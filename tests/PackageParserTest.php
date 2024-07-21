<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests;

use PHPUnit\Framework\TestCase;
use Rocky\PackageFiles\PackageParser;

final class PackageParserTest extends TestCase
{
    /** @test */
    public static function run_will_parse_the_working_directory_if_no_project_root_is_provided(): void
    {
        self::assertSame(
            $result = [
                'LICENSE',
                'README.md',
                'composer.json',
                'src' => [
                    'PackageParser.php',
                    'PathMatcher.php',
                    'PathMatcherComponent' => [
                        'AnyCharacterExceptDirectoryIndicator.php',
                        'AnyCharactersExceptDirectoryIndicator.php',
                        'Character.php',
                        'CharacterFromList.php',
                        'CharacterListComponent' => [
                            'CharacterListComponentInterface.php',
                            'CharacterRange.php',
                        ],
                        'CurrentDirectoryAndAnyLevelSubDirectory.php',
                        'DirectorySeparator.php',
                        'PathMatcherComponentInterface.php',
                    ],
                    'RuleParser.php',
                ],
            ],
            PackageParser::run(__DIR__ . DIRECTORY_SEPARATOR . '..', 5, 5)
        );
        self::assertSame($result, PackageParser::run(searchDepth: 5, resultDepth: 5));
    }

    /** @test */
    public static function run_will_reduce_the_search_depth_if_provided(): void
    {
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src' => [
                    'PackageParser.php',
                    'PathMatcher.php',
                    'PathMatcherComponent',
                    'RuleParser.php',
                ],
            ],
            PackageParser::run(searchDepth: 2, resultDepth: 5)
        );
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src'
            ],
            PackageParser::run(resultDepth: 5)
        );
    }

    /** @test */
    public static function run_will_reduce_the_result_depth_if_provided(): void
    {
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src' => [
                    'PackageParser.php',
                    'PathMatcher.php',
                    'PathMatcherComponent' => [
                        'AnyCharacterExceptDirectoryIndicator.php',
                        'AnyCharactersExceptDirectoryIndicator.php',
                        'Character.php',
                        'CharacterFromList.php',
                        'CharacterListComponent',
                        'CurrentDirectoryAndAnyLevelSubDirectory.php',
                        'DirectorySeparator.php',
                        'PathMatcherComponentInterface.php',
                    ],
                    'RuleParser.php',
                ],
            ],
            PackageParser::run(searchDepth: 6, resultDepth: 3)
        );
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src'
            ],
            PackageParser::run(searchDepth: 6)
        );
    }

    /** @test */
    public static function run_will_reduce_the_result_depth_to_the_minimum(): void
    {
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src'
            ],
            PackageParser::run()
        );
    }
}