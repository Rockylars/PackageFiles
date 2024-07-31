<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests\PackageParser;

use Rocky\PackageFiles\PackageParser;
use Rocky\PackageFiles\Tests\ProjectGeneratingTestCase;

final class C4_DirectoryPatternTest extends ProjectGeneratingTestCase
{
    /** @test */
    public static function pattern_4A_run_will_match_from_root_only_with_said_pattern(): void
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
            'root_folder' => [],
            '.gitignore' => <<<TXT
/name_a
/root_folder/
TXT,
        ]);
        self::assertSame(
            [
                '.gitignore',
                'name_b',
                'some_folder' => [
                    'another_folder' => [
                        'name_a' => [
                            'name_b'
                        ],
                    ],
                    'name_a',
                    'name_b',
                ],
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }

    /** @test */
    public static function pattern_4B_run_will_match_any_file_or_directory_matching_the_exact_name(): void
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
                    'another_folder/',
                    'name_b',
                ],
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }

    /** @test */
    public static function pattern_4C_run_will_follow_the_directory_depth_from_root_with_any_directory_indicator(): void
    {
        self::createFileStructure([
            'some_folder' => [
                'name_a' => '',
                'name_b' => '',
                'another_folder' => [
                    'name_a' => [
                        'name_b' => ''
                    ],
                ],
                'some_folder' => [
                    'another_folder' => [
                        'name_a' => [
                            'name_b' => ''
                        ],
                    ]
                ]
            ],
            'folder_b' => [
                'name_a' => '',
                'name_b' => '',
            ],
            'name_a' => '',
            'name_b' => '',
            '.gitignore' => <<<TXT
some_folder/another_folder/name_a
/folder_b/name_b

folder_b\/name_a
another_folder/name_a
TXT,
        ]);
        self::assertSame(
            [
                '.gitignore',
                'folder_b' => [
                    'name_a'
                ],
                'name_a',
                'name_b',
                'some_folder' => [
                    'another_folder/',
                    'name_a',
                    'name_b',
                    'some_folder' => [
                        'another_folder' => [
                            'name_a' => [
                                'name_b'
                            ],
                        ],
                    ],
                ],
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }

    /** @test */
    public static function pattern_4G_run_will_match_any_directory_matching_the_exact_name(): void
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
                'name_a',
                'name_b',
                'some_folder' => [
                    'another_folder/',
                    'name_a',
                    'name_b',
                ],
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }
}