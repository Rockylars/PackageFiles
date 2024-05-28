<?php

declare(strict_types=1);

namespace Rocky\PackageFiles;

use Exception;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\PcreException;

// You must first parse the gitignore to see if a gitattributes file got ignored, that one will not be accounted for.
// Apparently \ also works for directory paths according to PHPStorm, which will complicate things a lot, it seems to only disable the special characters.

// *.txt     ignoring the rules coming in from above
// !*.txt    counteracting the rules coming in from above

//   /dfdf   start with spaces that should be trimmed.
//  #  xxx   ignore comments
//           ignore blank lines

// !/dfdf    inversion, allows you to ignore certain parts of a rule, such as a whole folder ignore with a file/folder match inversion will exclude the whole folder except the path towards this inverted file/folder, this can also have new ignores on top of it again as it is order based.
// aa!dfdf   special character ! in file/folder name

// /xxxxxx   name search file/folder in root directory only
// xxxxx     name search file/folder in any lower directory
// xx/xx     name search file/folder in subdirectory starting from folder root path, even when not starting with /
// **/xx     name search file/folder in any lower directory (is supposed to exclude files from this but it does not seem to be the case), this also does a directory search but now starts from anywhere.
// xx/**/xx  name search file/folder in any subdirectory starting from mentioned root subdirectory which then follows another subdirectory path somewhere down the line
// xxxx/     name search only folders, files are excluded

// xx*       zero to infinite characters
// xx?       zero to one character(s)

// aa\*      escaped special character
// aa\t      escapes a non special character which leads to the behaviour of /, name search file/folder in subdirectory starting from folder root path, even when not starting with /
// aa\\      escaped escape character, not sure why as you can't use this character in a file or directory name.
// aa\\\\*   chained escaped character solving, very low priority as there's absolutely no use to this.

// [sS]d.txt range operator to match one character with set
// [!S]e.txt range operator to match one character with set exclusion
// [a-z]aa   range operator to match one character with range
// [!a-c]bb  range operator to match one character with range exclusion? Test this.
// []]       range operator to match one character  with ] valid as first character.
// [3]]      range operator to match one character with ] invalid not as first character.
// [3!]      range operator to match one character with ! valid as not first character.
// [\]]      range operator to match one character with ] valid as escaped character.

// [pdf]     ignore git attributes stuff that isn't export-ignore
// sdfd  ex- have spaces between file and export-ignore

final class RuleParser
{
    public static function run(string $line, bool $doExtraThingsForGitAttributes): IgnoreRule|null
    {
        //   /dfdf   start with spaces that should be trimmed.
        $line = trim($line);

        //           ignore blank lines
        if ($line === '') {
            return null;
        }

        //  #  xxx   ignore comments
        if (str_starts_with($line, '#')) {
            return null;
        }

        if ($doExtraThingsForGitAttributes) {
            // [pdf]     ignore git attributes stuff that isn't export-ignore
            if (!str_ends_with($line, ' export-ignore')) {
                return null;
            }

            // sdfd  ex- have spaces between file and export-ignore
            $line = rtrim(substr($line, 0, -14));
        }

        $rule = new IgnoreRule(
            // !/dfdf    inversion, allows you to ignore certain parts of a rule, such as a whole folder ignore with a file/folder match inversion will exclude the whole folder except the path towards this inverted file/folder, this can also have new ignores on top of it again as it is order based.
            str_starts_with($line, '!')
        );

        
        return $rule;
    }
}
