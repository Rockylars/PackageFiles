<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests\PackageParser;

use Rocky\PackageFiles\PackageParser;
use Rocky\PackageFiles\Tests\ProjectGeneratingTestCase;

final class DirectoryPatternTest extends ProjectGeneratingTestCase
{
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