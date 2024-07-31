<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests;

use Rocky\PackageFiles\PackageParser;

final class PackageParserTest extends ProjectGeneratingTestCase
{

    /** @test */
    public static function run_will_reduce_the_search_depth_if_provided(): void
    {
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src/PackageParser.php',
                'src/PathMatcher.php',
                'src/PathMatcherComponent/',
                'src/RuleParser.php',
            ],
            PackageParser::run(searchDepth: 2, resultDepth: 5, resultAsOneDimensionalArray: true)
        );
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src/'
            ],
            PackageParser::run(resultDepth: 5, resultAsOneDimensionalArray: true)
        );

        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src' => [
                    'PackageParser.php',
                    'PathMatcher.php',
                    'PathMatcherComponent/',
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
                'src/'
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
                'src/PackageParser.php',
                'src/PathMatcher.php',
                'src/PathMatcherComponent/AnyCharacterExceptDirectoryIndicator.php',
                'src/PathMatcherComponent/Anything.php',
                'src/PathMatcherComponent/Character.php',
                'src/PathMatcherComponent/CharacterFromList.php',
                'src/PathMatcherComponent/CharacterListComponent/',
                'src/PathMatcherComponent/CurrentDirectoryAndAnyLevelSubDirectory.php',
                'src/PathMatcherComponent/DirectorySeparator.php',
                'src/PathMatcherComponent/NothingOrAnyCharactersExceptDirectoryIndicator.php',
                'src/PathMatcherComponent/PathMatcherComponentInterface.php',
                'src/RuleParser.php'
            ],
            PackageParser::run(searchDepth: 6, resultDepth: 3, resultAsOneDimensionalArray: true)
        );
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src/'
            ],
            PackageParser::run(searchDepth: 6, resultAsOneDimensionalArray: true)
        );

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
                        'Anything.php',
                        'Character.php',
                        'CharacterFromList.php',
                        'CharacterListComponent/',
                        'CurrentDirectoryAndAnyLevelSubDirectory.php',
                        'DirectorySeparator.php',
                        'NothingOrAnyCharactersExceptDirectoryIndicator.php',
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
                'src/'
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
                'src/'
            ],
            PackageParser::run(resultAsOneDimensionalArray: true)
        );

        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src/'
            ],
            PackageParser::run()
        );
    }

    /** @test */
    public static function run_can_add_additional_formatting(): void
    {
        self::assertSame(
            [
                'LICENSE' => 'file',
                'README.md' => 'file',
                'composer.json' => 'file',
                'src/PackageParser.php' => 'file',
                'src/PathMatcher.php' => 'file',
                'src/PathMatcherComponent/' => 'folder',
                'src/RuleParser.php' => 'file',
            ],
            PackageParser::run(searchDepth: 2, resultDepth: 5, additionalFormatting: true, resultAsOneDimensionalArray: true)
        );
        self::assertSame(
            [
                'LICENSE' => 'file',
                'README.md' => 'file',
                'composer.json' => 'file',
                'src/' => 'folder',
            ],
            PackageParser::run(additionalFormatting: true, resultAsOneDimensionalArray: true)
        );

        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src' => [
                    'PackageParser.php',
                    'PathMatcher.php',
                    'PathMatcherComponent' => [],
                    'RuleParser.php',
                ],
            ],
            PackageParser::run(searchDepth: 2, resultDepth: 5, additionalFormatting: true)
        );
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src' => [],
            ],
            PackageParser::run(additionalFormatting: true)
        );
    }

    /** @test */
    public static function run_can_skip_deep_search_on_massive_folders(): void
    {
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src/'
            ],
            PackageParser::run(searchDepth: 10, resultDepth: 10, resultAsOneDimensionalArray: true, pathsToBigFoldersToSkipDeepSearchOn: [
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src',
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor'
            ])
        );
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src/'
            ],
            PackageParser::run(resultAsOneDimensionalArray: true, pathsToBigFoldersToSkipDeepSearchOn: [
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src',
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor'
            ])
        );

        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src/'
            ],
            PackageParser::run(searchDepth: 10, resultDepth: 10, pathsToBigFoldersToSkipDeepSearchOn: [
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src',
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor'
            ])
        );
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src/'
            ],
            PackageParser::run(pathsToBigFoldersToSkipDeepSearchOn: [
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src',
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor'
            ])
        );
    }

    /** @test */
    public static function run_will_work_with_an_empty_directory(): void
    {
        self::createFileStructure([]);
        self::assertSame(
            [],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }

    /** @test */
    public static function run_will_parse_the_working_directory_if_no_project_root_is_provided(): void
    {
        self::assertSame(
            $result1D = [
                'LICENSE',
                'README.md',
                'composer.json',
                'src/PackageParser.php',
                'src/PathMatcher.php',
                'src/PathMatcherComponent/AnyCharacterExceptDirectoryIndicator.php',
                'src/PathMatcherComponent/Anything.php',
                'src/PathMatcherComponent/Character.php',
                'src/PathMatcherComponent/CharacterFromList.php',
                'src/PathMatcherComponent/CharacterListComponent/CharacterListComponentInterface.php',
                'src/PathMatcherComponent/CharacterListComponent/CharacterRange.php',
                'src/PathMatcherComponent/CurrentDirectoryAndAnyLevelSubDirectory.php',
                'src/PathMatcherComponent/DirectorySeparator.php',
                'src/PathMatcherComponent/NothingOrAnyCharactersExceptDirectoryIndicator.php',
                'src/PathMatcherComponent/PathMatcherComponentInterface.php',
                'src/RuleParser.php'
            ],
            PackageParser::run(__DIR__ . DIRECTORY_SEPARATOR . '..', 5, 5, resultAsOneDimensionalArray: true)
        );
        self::assertSame($result1D, PackageParser::run(searchDepth: 5, resultDepth: 5, resultAsOneDimensionalArray: true));

        self::assertSame(
            $result2D = [
                'LICENSE',
                'README.md',
                'composer.json',
                'src' => [
                    'PackageParser.php',
                    'PathMatcher.php',
                    'PathMatcherComponent' => [
                        'AnyCharacterExceptDirectoryIndicator.php',
                        'Anything.php',
                        'Character.php',
                        'CharacterFromList.php',
                        'CharacterListComponent' => [
                            'CharacterListComponentInterface.php',
                            'CharacterRange.php',
                        ],
                        'CurrentDirectoryAndAnyLevelSubDirectory.php',
                        'DirectorySeparator.php',
                        'NothingOrAnyCharactersExceptDirectoryIndicator.php',
                        'PathMatcherComponentInterface.php',
                    ],
                    'RuleParser.php',
                ],
            ],
            PackageParser::run(__DIR__ . DIRECTORY_SEPARATOR . '..', 5, 5)
        );
        self::assertSame($result2D, PackageParser::run(searchDepth: 5, resultDepth: 5));
    }

    /** @test */
    public static function run_will_work_with_no_matching_ignores(): void
    {
        self::createFileStructure([
            '.gitignore' => <<<TXT
something
TXT,
            '.gitattributes' => <<<TXT
something_two export-ignore
TXT,

        ]);
        self::assertSame(
            [
                '.gitattributes',
                '.gitignore',
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }
}