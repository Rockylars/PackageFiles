<?php

declare(strict_types=1);

namespace Rocky\PackageFiles;

use Exception;
use Rocky\PackageFiles\PathMatcherComponent\AnyCharacterExceptDirectoryIndicator;
use Rocky\PackageFiles\PathMatcherComponent\AnyCharactersExceptDirectoryIndicator;
use Rocky\PackageFiles\PathMatcherComponent\CurrentDirectoryAndAnyLevelSubDirectory;
use Rocky\PackageFiles\PathMatcherComponent\Character;
use Rocky\PackageFiles\PathMatcherComponent\DirectorySeparator;

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
// dfsadf/**/adfdf/**/fdd TODO
// dadf/**/**/fdd TODO
// \a\a TODO
// \**\ TODO...???

// xx*       zero to infinite characters
// xx?       zero to one character(s) TODO: seems to however always just be "one character", check if the other is maybe also one to infinite instead.

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
// [\\] TODO

// [pdf]     ignore git attributes stuff that isn't export-ignore
// sdfd  ex- have spaces between file and export-ignore

final class RuleParser
{
    /**
     * @throws Exception
     * <br> > If the line could not be split into characters
     * <br> > If the line contained zero characters after splitting, which should be impossible as those were already returned.
     */
    public static function run(string $line, bool $doExtraThingsForGitAttributes): PathMatcher|null
    {
        //   /dfdf   start with spaces that should be trimmed.
        //TODO: Check if there's more whitespace characters we need to trim, UTF8 has a bunch of weird invisible characters.
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

            // Remove the ' export-ignore' part to process it like a normal git ignore rule.
            $line = substr($line, 0, -14);

            // sdfd  ex- have spaces between file and export-ignore
            $line = rtrim($line);
        }

        $characters = mb_str_split($line);
        if (!is_array($characters)) {
            throw new Exception('Could not convert the line \'' . $line . '\' to characters, ' . get_debug_type($characters) . ' returned');
        }
        $characterCount = count($characters);
        if ($characterCount === 0) {
            throw new Exception('Something went wrong in converting the line \'' . $line . '\' as we should not have received an empty set of characters by this point');
        }

        $rule = new PathMatcher();
        $canCheckForFirstCharacter = true;
        for ($i = 0; $i < $characterCount; $i++) {
            $character = $characters[$i];

            // Check for first characters, those will not be added as part of the RegExp in a direct way.
            if ($canCheckForFirstCharacter) {
                // !/dfdf    inversion, allows you to ignore certain parts of a rule, such as a whole folder ignore with a file/folder match inversion will exclude the whole folder except the path towards this inverted file/folder, this can also have new ignores on top of it again as it is order based.
                if ($character === '!') {
                    if (!in_array($characters[$i + 1] ?? '', ['/', '\\'])) {
                        $canCheckForFirstCharacter = false;
                    }
                    continue;
                }
                // Check for '\', has top priority.
                elseif ($character === '\\') {
                    // Do a check if this grabs a directory, otherwise handle it as a normal character being escaped.
                }
                // Check for '/'.
                elseif ($character === '/') {
                    $canCheckForFirstCharacter = false;
                    $rule->setTargetsCheckingParentDirectories();
                    //TODO: What if it ends with /**/?
                    if ($characters[$i + 1] ?? '' === '*') {
                        $rule->addPathComponent(new CurrentDirectoryAndAnyLevelSubDirectory());
                        $i += 3;
                        continue;
                    }
                    continue;
                }
                else {
                    // Let it stay on this character by going to the checks below.
                    $canCheckForFirstCharacter = false;
                }
            }

            // Check for '\', has top priority.
            if ($character === '\\') {
                // Big stuff.
            }
            // Check for '/'.
            elseif ($character === '/') {
                if ($i === $characterCount - 1) {
                    $rule->setTargetsOnlyDirectories();
                }
                //TODO: What if it ends with /**/?
                elseif ($characters[$i + 1] ?? '' === '*') {
                    $rule->setTargetsCheckingParentDirectories();
                    $rule->addPathComponent(new CurrentDirectoryAndAnyLevelSubDirectory());
                    $i += 3;
                }
                else {
                    $rule->setTargetsCheckingParentDirectories();
                    $rule->addPathComponent(new DirectorySeparator());
                }
            }
            // Check for '*'.
            elseif ($character === '*') {
                $rule->addPathComponent(new AnyCharactersExceptDirectoryIndicator());
            }
            // Check for '?'.
            elseif ($character === '?') {
                $rule->addPathComponent(new AnyCharacterExceptDirectoryIndicator());
            }
            // Check for '['.
            elseif ($character === '[') {
                // Big loop time.
            }
            // Otherwise, it is just a normal character, which still needs a RegExp escape character in case it's a '.' or whatnot.
            else {
                $rule->addPathComponent(new Character($character));
            }
        }
        return $rule;
    }
}
