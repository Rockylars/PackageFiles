<?php

declare(strict_types=1);

namespace Rocky\PackageFiles;

use Exception;
use Rocky\PackageFiles\PathMatcherComponent\AnyCharacterExceptDirectoryIndicator;
use Rocky\PackageFiles\PathMatcherComponent\NothingOrAnyCharactersExceptDirectoryIndicator;
use Rocky\PackageFiles\PathMatcherComponent\CurrentDirectoryAndAnyLevelSubDirectory;
use Rocky\PackageFiles\PathMatcherComponent\Character;
use Rocky\PackageFiles\PathMatcherComponent\DirectorySeparator;

// NOT MATCHES
// [pdf]     [1A] ignore git attributes stuff that isn't export-ignore
//           [1B] ignore blank or whitespace only lines and also file/folder name that are just spaces as those do not match in GIT without another character or the escape character.
// #  xxx    [1C] ignore comments
//    #  xxx [1D] ignore comments starting with just whitespace
// !         [1E] ignore nothing matcher.
// /         [1F] ignore nothing matcher.
// !/        [1G] ignore nothing matcher.
// //        [1H] ignore anything with two consecutive unescaped directory indicators as empty folder names will never match.
// \         [1I] nothing (including no whitespace) behind an unescaped escape character will make it match nothing, also not working as a directory marker.
// \/        [1J] files or directories can never have a slash in their name, so escaping it will simply match to nothing, this also has a problem of us needing to create our own directory separator for when / is somehow in a file or directory name.

// SPACES
//   dfdf    [2A] spaces/tabs/other before will count as characters, even entirely whitespace folders by just '   /' for example.
// dfdf      [2B] spaces/tabs/other after will not match in GIT.
// dfdf\     [2C] one trailing space/tab/other after will match for each backslash.
// dfdf   /  [2D] spaces/tabs/other after will match, as long as it has a character to end it.
// !  dfdf   [2E] inversion with spaces as file/folder name characters
// sdfd  ex- [2F] spaces/tabs/other file between export-ignore in .gitattributes are ignored and follow the same rules as the other ones.
// -nore     [2G] spaces/tabs/other file after export-ignore in .gitattributes are ignored and follow the same rules as the other ones.

// INVERSION
// *.txt     [3A] ignoring the rules coming in from above
// !*.txt    [3B] counteracting the rules coming in from above
// !/dfdf    [3C] inversion, allows you to ignore certain parts of a rule, such as a whole folder ignore with a file/folder match inversion will exclude the whole folder except the path towards this inverted file/folder, this can also have new ignores on top of it again as it is order based.
// aa!dfdf   [3D] special character ! in file/folder name

// DIRECTORIES
// /xxxxxx   [4A] name search file/folder in root directory only
// xxxxx     [4B] name search file/folder in any lower directory
// xx/xx     [4C] name search file/folder in subdirectory starting from folder root path, even when not starting with /
// **/xx     [4D] name search file/folder in any lower directory (is supposed to exclude files from this but it does not seem to be the case), this also does a directory search but now starts from anywhere.
// xx/**     [4E] match any file/folder in any lower directory.
// xx/**/xx  [4F] name search file/folder in any subdirectory starting from mentioned root subdirectory which then follows another subdirectory path somewhere down the line
// xxxx/     [4G] name search only folders, files are excluded, also works with /**/
// **/**/a   [4H] TODO: test directory chaining
// **/a/**/a [4I] TODO: test directory chaining
// a/**/**   [4J] TODO: test directory chaining

// CHARACTER BLANKS
// xx*       [5A] zero to infinite characters that are not directory indicators
// xx?       [5B] a character that is not a directory indicator

// ESCAPE CHARACTER
// aa\*      [6A] escaped character
// aa\\*     [6B] escaped escape character (not testable in Windows, file hides itself in Linux PHPStorm)
// aa\\\*    [6C] chained escaped character with escaped escape character
// aa\\\\*   [6D] chained escaped characters

// RANGE OPERATOR
// [sS]d.txt [7A] range operator to match one character with set
// [!S]e.txt [7B] range operator to match one character with set exclusion
// [a-z]aa   [7C] range operator to match one character with range
// [!a-c]bb  [7D] range operator to match one character with range exclusion? Test this.
// []]       [7E] range operator to match one character  with ] valid as first character.
// [3]]      [7F] range operator to match one character with ] invalid not as first character.
// [3!]      [7G] range operator to match one character with ! valid as not first character.
// [\]]      [7H] range operator to match one character with ] valid as escaped character.
// [\\] TODO

final class RuleParser
{
    /**
     * You must first parse the gitignore to see if a gitattributes file got ignored, that one will not be accounted for.
     * @throws Exception
     * <br> > If the line could not be split into characters
     * <br> > If the line contained zero characters after splitting, which should be impossible as those were already returned.
     */
    public static function run(string $line, bool $doExtraThingsForGitAttributes): PathMatcher|null
    {
        if ($doExtraThingsForGitAttributes) {
            // -nore     [2G] spaces/tabs/other file after export-ignore in .gitattributes are ignored and follow the same rules as the other ones.
            $line = rtrim($line);

            // [pdf]     [1A] ignore git attributes stuff that isn't export-ignore
            if (!str_ends_with($line, ' export-ignore')) {
                return null;
            }

            // Remove the ' export-ignore' part to process it like a normal git ignore rule.
            $line = substr($line, 0, -14);

            //TODO: Test git package with 'something\ export-ignore' and 'something\\ export-ignore'
//            if (str_ends_with($line, '\\')) {
//                return null;
//            }
        }

        // dfdf      [2B] spaces/tabs/other after will not match in GIT.
        // dfdf   /  [2D] spaces/tabs/other after will match, as long as it has a character to end it.
        // sdfd  ex- [2F] spaces/tabs/other file between export-ignore in .gitattributes are ignored and follow the same rules as the other ones.
        $rightTrimmedLine = rtrim($line);

        //           [1B] ignore blank or whitespace only lines and also file/folder name that are just spaces as those do not match in GIT without another character or the escape character.
        // !         [1E] ignore nothing matcher.
        // /         [1F] ignore nothing matcher.
        // !/        [1G] ignore nothing matcher.
        // //        [1H] ignore anything with two consecutive directory indicators as empty folder names will never match.
        // \/        [1J] files or directories can never have a slash in their name, so escaping it will simply match to nothing, this also has a problem of us needing to create our own directory separator for when / is somehow in a file or directory name.
        if (in_array($rightTrimmedLine, ['', '!', '/', '!/', '//', '!//', '\/'], true)) {
            return null;
        }

        //  #  xxx   [1C] ignore comments
        //    #  xxx [1D] ignore comments starting with spaces
        //   dfdf    [2A] spaces/tabs/other before will count as characters, even entirely whitespace folders by just '   /' for example.
        if (str_starts_with(ltrim($rightTrimmedLine), '#')) {
            return null;
        }

        [$characters, $characterCount] = self::splitIgnoreLine($rightTrimmedLine);

        if (str_ends_with($rightTrimmedLine, '\\')) {
            $escapeCharacters = 1;
            for ($i = $characterCount - 2; $i >= 0; $i--) {
                $character = $characters[$i];
                if ($character !== '\\') {
                    break;
                }
                $escapeCharacters++;
            }
            if ($escapeCharacters % 2 === 1) {
                // \         [1I] nothing (including no whitespace) behind an unescaped escape character will make it match nothing, also not working as a directory marker.
                if ($line === $rightTrimmedLine) {
                    return null;
                }

                // dfdf\     [2C] one trailing space/tab/other after will match for each backslash.
                [$charactersWithTrailingWhiteSpace] = self::splitIgnoreLine($line);
                $characters[] = $charactersWithTrailingWhiteSpace[$characterCount];
                $characterCount++;
            }
        }

        $previousCharacter = '';
        for ($i = 0; $i < $characterCount; $i++) {
            $character = $characters[$i];

            // //        [1H] ignore anything with two consecutive unescaped directory indicators as empty folder names will never match.
            if ($previousCharacter === '/' && $character === '/') {
                // Watch out with skips that include this character.
                return null;
            }

            // \/        [1J] files or directories can never have a slash in their name, so escaping it will simply match to nothing, this also has a problem of us needing to create our own directory separator for when / is somehow in a file or directory name.
            if ($character === '\\' && $characters[$i + 1] === '/') {
                return null;
            }

            $previousCharacter = $character;
        }

        $rule = new PathMatcher();
        $canCheckForFirstCharacter = true;
        $isInRangeMatcherLoop = false;
        for ($i = 0; $i < $characterCount; $i++) {
            $character = $characters[$i];

            // Check for first characters, those will not be added as part of the RegExp in a direct way.
            if ($canCheckForFirstCharacter) {
                // !/dfdf    [3C] inversion, allows you to ignore certain parts of a rule, such as a whole folder ignore with a file/folder match inversion will exclude the whole folder except the path towards this inverted file/folder, this can also have new ignores on top of it again as it is order based.
                if ($character === '!') {
                    $rule->setTargetsNotMatching();
                    continue;
                }
                // /xxxxxx   [4A] name search file/folder in root directory only
                elseif ($character === '/') {
                    $canCheckForFirstCharacter = false;
                    $rule->setTargetsCheckingParentDirectories();
                    //TODO: What if it ends with /**/?
//                    if (($characters[$i + 1] ?? '') === '*' && ($characters[$i + 2] ?? '') === '*' && ($characters[$i + 3] ?? '') === '/') {
//                        $rule->addPathComponent(new CurrentDirectoryAndAnyLevelSubDirectory(true));
//                        $i += 3;
//                        continue;
//                    }
                    continue;
                }
//                elseif ($character === '*' && ($characters[$i + 1] ?? '') === '*' && ($characters[$i + 2] ?? '') === '/') {
//                    $canCheckForFirstCharacter = false;
//                    $rule->setTargetsCheckingParentDirectories();
//                    $rule->addPathComponent(new CurrentDirectoryAndAnyLevelSubDirectory(true));
//                    $i += 1;
//                    continue;
//                }
                else {
                    // Let it stay on this character by going to the checks below.
                    $canCheckForFirstCharacter = false;
                }
            }

            if ($isInRangeMatcherLoop) {
                // TODO: Big loop time.
                return null;
            }

            // aa\*      [6A] escaped character
            // aa\\*     [6B] escaped escape character (not testable in Windows, file hides itself in Linux PHPStorm)
            // aa\\\*    [6C] chained escaped character with escaped escape character.
            // aa\\\\*   [6D] chained escaped characters.
            if ($character === '\\') {
                $rule->addPathComponent(new Character($characters[$i + 1]));
                $i += 1;
            }
            // Directory search
            elseif ($character === '/') {
                // xxxx/     [4G] name search only folders, files are excluded, also works with /**/
                if ($i === $characterCount - 1) {
                    $rule->setTargetsOnlyDirectories();
                }
//                elseif (($characters[$i + 1] ?? '') === '*' && ($characters[$i + 2] ?? '') === '*' && ($characters[$i + 3] ?? '') === '/') {
//                    $rule->setTargetsCheckingParentDirectories();
//                    $rule->addPathComponent(new CurrentDirectoryAndAnyLevelSubDirectory(false));
//                    $i += 2;
//                }
                else {
                    $rule->setTargetsCheckingParentDirectories();
                    $rule->addPathComponent(new DirectorySeparator());
                }
            }
            // xx*       [5A] zero to infinite characters that are not directory indicators
            elseif ($character === '*') {
                $rule->addPathComponent(new NothingOrAnyCharactersExceptDirectoryIndicator());
            }
            // xx?       [5B] a character that is not a directory indicator
            elseif ($character === '?') {
                $rule->addPathComponent(new AnyCharacterExceptDirectoryIndicator());
            }
            // Enter the range operator loop on each character to come.
            elseif ($character === '[') {
                $isInRangeMatcherLoop = true;
            }
            //   dfdf    [2A] spaces/tabs/other before will count as characters, even entirely whitespace folders by just '   /' for example.
            else {
                $rule->addPathComponent(new Character($character));
            }
        }
        return $rule;
    }

    /**
     * @return array{0: non-empty-array<int<0, max>, non-empty-string>, 1: int<1, max>}
     * @throws Exception
     */
    private static function splitIgnoreLine(string $line): array
    {
        $characters = mb_str_split($line, encoding: 'UTF-8');
        if (!is_array($characters)) {
            throw new Exception('Could not convert the line \'' . $line . '\' to characters, ' . get_debug_type($characters) . ' returned');
        }
        $characterCount = count($characters);
        if ($characterCount === 0) {
            throw new Exception('Something went wrong in converting the line \'' . $line . '\' as we should not have received an empty set of characters by this point');
        }
        /** @var non-empty-array<int<0, max>, non-empty-string> $characters */
        return [$characters, $characterCount];
    }
}
