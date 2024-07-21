<?php

declare(strict_types=1);

namespace Rocky\PackageFiles;

use Exception;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\PcreException;

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
        $projectRoot = $projectRoot === null ? \Safe\getcwd() : \Safe\realpath($projectRoot);

        //TODO: Test what happens with an empty root directory, we will need to not add an extra separator in those cases, such as the search and the rule processing.
        //$projectRoot = $projectRoot ?: null;
        if ($projectRoot === '') {
            throw new Exception('Add options for this, make sure to not add an extra directory separator');
        }

        $projectContents = self::search($projectRoot, $searchDepth);
        $projectContentsList = self::flattenDirectory($projectContents, $searchDepth);
        self::processGitRulesFiles($projectContents, $projectContentsList, false);
        //TODO: Remove these below later.
        $projectContents['.idea']['included'] = false;
        $projectContents['output']['included'] = false;
        $projectContents['vendor']['included'] = false;
        //TODO: Test what happens if you ignore a gitkeep file, what will happen?
        self::removeExcludedContent($projectContents);
        self::processGitRulesFiles($projectContents, $projectContentsList, true);
        self::removeExcludedContent($projectContents);
        //TODO: Add an alternative return where it is a one dimensional array of the full path.
        return self::summarize($projectContents, $resultDepth);
    }

    /**
     * @param non-empty-string $directoryPath
     * @param int<1, max> $maxDepth
     * @param int<1, max> $currentDepth
     * @param array<int<0, max>, non-empty-string> $route
     * @param string $localizedDirectoryPath
     * @return array<non-empty-string, mixed>
     * @throws DirException
     */
    private static function search(string $directoryPath, int $maxDepth, int $currentDepth = 1, array $route = [], string $localizedDirectoryPath = ''): array
    {
        /** @var array<int, string> $contents */
        $contents = \Safe\scandir($directoryPath);
        $contentCount = count($contents);

        $foldersExcluded = ['.', '..'];
        if ($isInProjectRoot = $currentDepth === 1) {
            $foldersExcluded[] = '.git';
        }

        $parsedContents = [];
        for ($i = 0; $i < $contentCount; $i++) {
            $fileOrFolderName = $contents[$i];
            if (in_array($fileOrFolderName, $foldersExcluded, true)) {
                continue;
            }
            // TODO: Test issues with string integers folder names.
            $localizedPath = $isInProjectRoot ? $fileOrFolderName : $localizedDirectoryPath . DIRECTORY_SEPARATOR . $fileOrFolderName;
            $parsedContents[$fileOrFolderName] = [
                'is_directory' => $isDir = is_dir($path = $directoryPath . DIRECTORY_SEPARATOR . $fileOrFolderName),
                'path' => $path,
                'localized_path' => $localizedPath,
                'included' => true,
                'route' => $deeperRoute = array_merge($route, [$fileOrFolderName])
            ];
            if ($isDir && $currentDepth < $maxDepth) {
                $parsedContents[$fileOrFolderName]['contents'] = self::search($path, $maxDepth, $currentDepth + 1, $deeperRoute, $localizedPath);
            }
        }
        return $parsedContents;
    }

    /**
     * @param array<non-empty-string, mixed> $directory
     * @param int<1, max> $currentDepth
     * @param int<1, max> $maxDepth
     * @return array<int, mixed>
     */
    private static function flattenDirectory(array &$directory, int $maxDepth, int $currentDepth = 1): array
    {
        $flatList = [];
        foreach ($directory as $fileOrFolderName => $info) {
            $flatList[$info['localized_path']] = [
                'localized_route' => $info['route'],
                // Do not replace $directory[$fileOrFolderName] with $info, we're trying to create a reference here.
                'data' => &$directory[$fileOrFolderName]
            ];
            if ($info['is_directory'] && $currentDepth < $maxDepth) {
                // Do not replace $directory[$fileOrFolderName] with $info, we're trying to create a reference here.
                $flatList = array_merge($flatList, self::flattenDirectory($directory[$fileOrFolderName]['contents'], $maxDepth, $currentDepth + 1));
            }
        }
        return $flatList;
    }

    /**
     * @param array<non-empty-string, mixed> $directory
     * @param array<non-empty-string, array<int<0, max>, non-empty-string>> $flatList
     * @throws FilesystemException
     */
    private static function processGitRulesFiles(array &$directory, array &$flatList, bool $isSecondRoundForGitAttributes): void
    {
        $gitRulesFileName = $isSecondRoundForGitAttributes ? '.gitattributes' : '.gitignore';
        if (array_key_exists($gitRulesFileName, $directory)) {
            //var_dump('- FILE - ' . $directory[$gitRulesFileName]['path']);
            $lines = explode("\n", \Safe\file_get_contents($directory[$gitRulesFileName]['path']));
            foreach ($lines as $line) {
                $rule = RuleParser::run($line, $isSecondRoundForGitAttributes);
                if ($rule === null) {
                    continue;
                }
                self::processRule($directory, $flatList, $rule);
            }
        }
        foreach ($directory as $fileOrFolderName => $info) {
            // The search depth will make some directories not fetch their contents.
            if ($info['is_directory'] && isset($info['contents'])) {
                // Do not replace $directory[$fileOrFolderName] with $info, we're trying to create a reference here.
                self::processGitRulesFiles($directory[$fileOrFolderName]['contents'], $flatList, $isSecondRoundForGitAttributes);
            }
        }
    }

    /**
     * @param array<non-empty-string, mixed> $directory
     * @param array<non-empty-string, array<non-empty-string, mixed> $flatList
     * @throws PcreException
     */
    private static function processRule(array &$directory, array &$flatList, PathMatcher $rule): void
    {
        foreach ($flatList as $localizedFileOrFolderPath => $info) {
            if ($rule->targetsMatching()) {
                // TODO: Do the preg match and all "matches + is dir if dir targeting is on" will be marked as "not included"
            } else {
                // TODO: Do the preg match and all either "not matches" or "matches but is not directory while dir targeting" will be marked as "included"
                // TODO: Go from the root to the not matched file/folder to mark each of those directories as "included" to ensure an ignore plus include pattern will work, but you only have to do this once.
            }

            // TODO: Switch it for the local directory so we don't wrongly target the incorrect files.
            $matches = [];
            if (\Safe\preg_match('/'. $rule->asRegExp() . '/u', $localizedFileOrFolderPath, $matches)) {
                // You can have multiple matches, but not per single full path.
                if (count($matches) > 1) {
                    throw new Exception('Encountered more than one match for ' . $localizedFileOrFolderPath . ' through ' . $rule->asRegExp());
                }
                if ($rule->targetsOnlyDirectories() && !$info['data']['is_directory']) {
                    var_dump('-- SKIP - NOT DIR ------ ' . $rule->asRegExp() . ' => ' . $localizedFileOrFolderPath);
                    continue;
                }
                if ($rule->targetsMatching())
                // TODO: This is working, but it is not localizing the expressions onto the local directory yet.
                $info['data']['included'] = false;
                //var_dump('-- MATCH --------------- ' . $rule->asRegExp() . ' => ' . $localizedFileOrFolderPath);
            }
        }
        return;

        // Rules can not look up, and they will always take the current directory of the .gitignore/.gitattributes file as their root.
        // Rules that counteract the rules before it will run as the new rule for the files/folders it applies to.
        foreach ($directory as $fileOrFolderName => $info) {
            // The search depth will make some directories not fetch their contents.
            if (/** TODO: If rule influences more layers */ $info['is_directory'] && isset($info['contents'])) {
                // Do not replace $directory[$fileOrFolderName] with $info, we're trying to create a reference here.
//                self::processRule($directory[$fileOrFolderName]['contents'], $rule);
            }
            //TODO: Add a rule localizer for rules that influence deeper paths specifically and not all paths.
        }
    }

    private static function summarize(array $directory, int $maxDepth, int $currentDepth = 1): array
    {
        $contents = [];
        foreach ($directory as $fileOrFolderName => $info) {
            // The search depth will make some directories not fetch their contents.
            if ($info['is_directory'] && isset($info['contents']) && $currentDepth < $maxDepth) {
                $contents[$fileOrFolderName] = self::summarize($info['contents'], $maxDepth, $currentDepth + 1);
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
