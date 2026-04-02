<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests\PackageParser;

use Rocky\PackageFiles\PackageParser;
use Rocky\PackageFiles\Tests\ProjectGeneratingTestCase;

final class C5_CharacterBlanksTest extends ProjectGeneratingTestCase
{
    /** @test */
    public static function pattern_5A_star_can_match_no_characters_and_many_but_not_directory_separator(): void
    {
        self::createFileStructure([
            'name_a' => [
                'yimyam' => '',
            ],
            'brim' => [
                'yimyam' => '',
            ],
            'bleep' => [
                'yimyam' => '',
            ],
            'pear' => [
                'yimyam' => '',
            ],
            '.gitignore' => <<<TXT
/name*/
/bri*/
/bleep*yimyam
/pear*/
TXT,
        ]);
        self::assertSame(
            [
                '.gitignore',
                'bleep' => [
                    'yimyam'
                ],
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }

    /** @test */
    public static function pattern_5B_question_mark_can_match_one_but_not_none_or_many_and_not_directory_separator(): void
    {
        self::createFileStructure([
            'name_a' => [
                'yimyam' => '',
            ],
            'brim' => [
                'yimyam' => '',
            ],
            'bleep' => [
                'yimyam' => '',
            ],
            'pear' => [
                'yimyam' => '',
            ],
            '.gitignore' => <<<TXT
/name?/
/bri?/
/bleep?yimyam
/pear?s/
TXT,
        ]);
        self::assertSame(
            [
                '.gitignore',
                'bleep' => [
                    'yimyam'
                ],
                'name_a' => [
                    'yimyam'
                ],
                'pear' => [
                    'yimyam'
                ],
            ],
            PackageParser::run(self::TEST_DIRECTORY, 10, 10)
        );
        self::removeFileStructure();
    }
}