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

final class PackageParser
{
    /**
     * @param non-empty-string|null $projectRoot
     * @param int<1, max> $searchDepth
     * @param int<1, max> $resultDepth
     * @return array<int, mixed>
     * <br> > Returns a string array of `$searchDepth` in depth.
     * @throws Exception
     * <br> > If `$directory` is not a directory
     * @throws FilesystemException
     * <br> > If the root .gitignore file can not be found
     * <br> > If the .gitattributes file can not be found
     * @throws DirException
     * <br> > If $projectRoot is not a directory
     * @throws PcreException
     * <br> > if the regex breaks
     */
    public static function run(
        string|null $projectRoot = null,
        int $searchDepth = 1,
        int $resultDepth = 1
    ): array
    {
        //TODO: Add a config to prevent deep searches (or all searches) in certain arrays, such as vendor.

        // region Convert the project root

        // Convert the path to a straight path to prevent debugging nightmares, using the current working directory if nothing is given.
        // If virtual paths are an issue, then we should copy one of the custom scripts people have already written.
        $projectRoot = $projectRoot === null ? \Safe\getcwd() : \Safe\realpath($projectRoot);

        // endregion

        // region Gather the project directories

        $projectContents = self::search($projectRoot, 1, $searchDepth);

        // endregion

        // region Parse all git ignore files

        self::processGitIgnoreFiles($projectContents);
        //TODO: Remove these below later.
        $projectContents['.idea']['included'] = false;
        $projectContents['output']['included'] = false;
        $projectContents['vendor']['included'] = false;

        // endregion

        // region Remove all ignored files/folders (test if locally, a file brought back through gitattributes, will be accepted in)

        self::removeExcludedContent($projectContents);

        // endregion

        // TODO: Parse all git attributes.

        // region Remove all ignored files/folders

        self::removeExcludedContent($projectContents);

        // endregion

        // region Simplify the result

        return self::summarize($projectContents, 1, $resultDepth);

        // endregion
    }

    /**
     * @param non-empty-string $directory
     * @param int<1, max> $currentDepth
     * @param int<1, max> $maxDepth
     * @return array<int, mixed>
     * @throws Exception
     */
    private static function search(string $directory, int $currentDepth, int $maxDepth): array
    {
        /** @var array<int, string> $contents */
        $contents = \Safe\scandir($directory);
        $contentCount = count($contents);

        $foldersExcluded = ['.', '..'];
        if ($currentDepth === 1) {
            $foldersExcluded[] = '.git';
        }

        $parsedContents = [];
        for ($i = 0; $i < $contentCount; $i++) {
            $fileOrFolderName = $contents[$i];
            if (in_array($fileOrFolderName, $foldersExcluded, true)) {
                continue;
            }
            $parsedContents[$fileOrFolderName] = [
                'is_directory' => $isDir = is_dir($path = $directory . DIRECTORY_SEPARATOR . $fileOrFolderName),
                'path' => $path,
                'included' => true
            ];
            if ($isDir && $currentDepth < $maxDepth) {
                $parsedContents[$fileOrFolderName]['contents'] = self::search($path, $currentDepth + 1, $maxDepth);
            }
        }
        return $parsedContents;
    }

    private static function processGitIgnoreFiles(array &$directory): void
    {
        if (array_key_exists('.gitignore', $directory)) {
            $lines = explode("\n", \Safe\file_get_contents($directory['.gitignore']['path']));
            foreach ($lines as $line) {
                RuleParser::run($line);
            }
        }
        foreach ($directory as $fileOrFolderName => $info) {
            // The search depth will make some directories not fetch their contents.
            if ($info['is_directory'] && isset($info['contents'])) {
                // Do not replace $directory[$fileOrFolderName] with $info, we're trying to create a reference here.
                self::processGitIgnoreFiles($directory[$fileOrFolderName]['contents']);
            }
        }
    }

    private static function processGitAttributesFiles(): void
    {

    }

    private static function processRule(): void
    {

    }

    private static function summarize(array $directory, int $currentDepth, int $maxDepth): array
    {
        $contents = [];
        foreach ($directory as $fileOrFolderName => $info) {
            if (!$info['included']) {
                continue;
            }
            // The search depth will make some directories not fetch their contents.
            if ($info['is_directory'] && isset($info['contents'])) {
                $contents[$fileOrFolderName] = self::summarize($info['contents'], $currentDepth + 1, $maxDepth);
            } else {
                $contents[] = $fileOrFolderName;
            }
        }
        return $contents;
    }

    private static function removeExcludedContent(array &$directory): void
    {
        foreach ($directory as $fileOrFolderName => $info) {
            if ($info['included']) {
                // The search depth will make some directories not fetch their contents.
                if ($info['is_directory'] && isset($info['contents'])) {
                    self::removeExcludedContent($info['contents']);
                } else {
                    continue;
                }
            } else {
                unset($directory[$fileOrFolderName]);
            }
        }
    }
}
