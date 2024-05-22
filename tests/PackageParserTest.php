<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests;

use PHPUnit\Framework\TestCase;
use Rocky\PackageFiles\PackageParser;

final class PackageParserTest extends TestCase
{
    /** @test */
    public static function simplePackageSearchWillExcludeAndIgnore(): void
    {
        // Update the README when updating this.
        self::assertSame(
            [
                'LICENSE',
                'README.md',
                'composer.json',
                'src'
            ],
            PackageParser::simplePackageSearch(__DIR__ . DIRECTORY_SEPARATOR . '..')
        );
    }

    /** @test */
    public static function s(): void
    {
        // You must first parse the gitignore to see if a gitattributes file got ignored, that one will not be accounted for.

        // TODO: ---
        // /xxxxxx   path search
        // xxxxx     name search
        // xx/xx     subdirectory
        // xx*       zero to infinite characters
        // xx?       zero to one character(s)
        // # xxx     ignore comments
        // [pdf]     ignore git attributes stuff that isn't export-ignore
        //   /dfdf   start with spaces that should be trimmed.
        // sdfd ex.. have spaces between file and export-ignore
        // !/dfdf    inversion
        // *.txt     ignoring the rules coming in from above
        // \*        escaped character
        // \\\\*     chained escaped character solving
        // [sS]*.txt range operator with set
        // [!S]*.txt range operator with set exclusion
        // [a-z]*    range operator with range
        // [!a-c]*   range operator with range exclusion? Test this.
        //           ignore blank lines
        // df*/      directory parsed instead of file matched, excluding files from this match.
        // []]       range operator with ] valid as first character.
        // [3]]      range operator with ] invalid not as first character.
        // [3!]      range operator with ! valid as not first character.

        // **/lib/name.file             Starting with ** before / specifies that it matches any folder in the repository. Not just on root.
        // **/name                      All name folders, and files and folders in any name folder.
        // /lib/**/name                 All name folders, and files and folders in any name folder within the lib folder.

        //TODO: More.

        //TODO: Figure out how the `export-subst` works.
    }
}