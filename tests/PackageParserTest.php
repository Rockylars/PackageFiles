<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Rocky\PackageFiles\PackageParser;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;

final class PackageParserTest extends TestCase
{
    private const TEST_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'fake_project';

    /**
     * @param array<string, mixed> $fileStructure
     * @throws FilesystemException
     */
    private static function createFileStructure(array $fileStructure, string $directoryPath = self::TEST_DIRECTORY): void
    {
        if ($directoryPath === self::TEST_DIRECTORY) {
            \Safe\mkdir(self::TEST_DIRECTORY);
        }

        // Unfortunately, I can only check this after creating the first folder.
        if (!str_starts_with($readablePath = \Safe\realpath($directoryPath), self::TEST_DIRECTORY)) {
            throw new Exception('Can not alter potentially dangerous path: ' . $readablePath);
        }

        /**
         * @var string $fileOrFolderName
         * @var string|array<string, mixed> $contents
         */
        foreach ($fileStructure as $fileOrFolderName => $contents) {
            $fileOrFolderPath = $directoryPath . DIRECTORY_SEPARATOR . $fileOrFolderName;
            if (is_array($contents)) {
                \Safe\mkdir($fileOrFolderPath);
                self::createFileStructure($contents, $fileOrFolderPath);
            } else {
                \Safe\file_put_contents($fileOrFolderPath, $contents);
            }
        }
    }

    /**
     * Has to go through the whole structure since PHP won't delete filled folders, this is safer anyway.
     * @throws FilesystemException
     * @throws DirException
     */
    private static function removeFileStructure(string $directoryPath = self::TEST_DIRECTORY): void
    {
        if (!str_starts_with($readablePath = \Safe\realpath($directoryPath), self::TEST_DIRECTORY)) {
            throw new Exception('Can not alter potentially dangerous path: ' . $readablePath);
        }
        $contents = \Safe\scandir($directoryPath);
        $contentCount = count($contents);
        for ($i = 0; $i < $contentCount; $i++) {
            $fileOrFolderName = $contents[$i];
            if (in_array($fileOrFolderName, ['.', '..'], true)) {
                continue;
            }
            if (is_dir($fileOrFolderPath = $directoryPath . DIRECTORY_SEPARATOR . $fileOrFolderName)) {
                self::removeFileStructure($fileOrFolderPath);
            } else {
                \Safe\unlink($fileOrFolderPath);
            }
        }
        \Safe\rmdir($directoryPath);
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
                'src/PathMatcherComponent/AnyCharactersExceptDirectoryIndicator.php',
                'src/PathMatcherComponent/Character.php',
                'src/PathMatcherComponent/CharacterFromList.php',
                'src/PathMatcherComponent/CharacterListComponent/CharacterListComponentInterface.php',
                'src/PathMatcherComponent/CharacterListComponent/CharacterRange.php',
                'src/PathMatcherComponent/CurrentDirectoryAndAnyLevelSubDirectory.php',
                'src/PathMatcherComponent/DirectorySeparator.php',
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
        self::assertSame($result2D, PackageParser::run(searchDepth: 5, resultDepth: 5));
    }

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
                'src/PathMatcherComponent/AnyCharactersExceptDirectoryIndicator.php',
                'src/PathMatcherComponent/Character.php',
                'src/PathMatcherComponent/CharacterFromList.php',
                'src/PathMatcherComponent/CharacterListComponent/',
                'src/PathMatcherComponent/CurrentDirectoryAndAnyLevelSubDirectory.php',
                'src/PathMatcherComponent/DirectorySeparator.php',
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
                        'AnyCharactersExceptDirectoryIndicator.php',
                        'Character.php',
                        'CharacterFromList.php',
                        'CharacterListComponent/',
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

    /** @test */
    public static function run_will_work_with_the_ability_to_remove_itself(): void
    {
        self::createFileStructure([
            '.gitignore' => <<<TXT
something
TXT,
            '.gitattributes' => <<<TXT
/.gitignore     export-ignore
/.gitattributes export-ignore
TXT,

        ]);
        self::assertSame(
            [
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }

    /** @test */
    public static function run_will_match_any_file_or_directory_matching_the_exact_name(): void
    {
        self::createFileStructure([
            'some_folder' => [
                'name_a' => '',
                'name_b' => '',
                'another_folder' => [
                    'name_a' => [
                        'name_b' => ''
                    ],
                ]
            ],
            'name_a' => '',
            'name_b' => '',
            '.gitignore' => <<<TXT
name_a
TXT,
        ]);
        self::assertSame(
            [
                '.gitignore',
                'name_b',
                'some_folder' => [
                    'another_folder',
                    'name_b',
                ],
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }

    /** @test */
    public static function run_will_match_any_directory_matching_the_exact_name(): void
    {
        self::createFileStructure([
            'some_folder' => [
                'name_a' => '',
                'name_b' => '',
                'another_folder' => [
                    'name_a' => [
                        'name_b' => ''
                    ],
                ]
            ],
            'name_a' => '',
            'name_b' => '',
            '.gitignore' => <<<TXT
name_a/
TXT,
        ]);
        self::assertSame(
            [
                '.gitignore',
                'name_b',
                'some_folder' => [
                    'another_folder',
                    'name_b',
                ],
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }
}