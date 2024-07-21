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
     * @param array<int<0, max>, non-empty-string> $pathsToBigFoldersToSkipDeepSearchOn To skip deep search on bulky things like the "vendor" folder or any compiled JS cache folders that you know will be ignored entirely/mostly anyway.
     * @return array<int, mixed>
     * @throws Exception
     * @throws FilesystemException
     * @throws DirException
     * @throws PcreException
     */
    public static function run(
        string|null $projectRoot = null,
        int $searchDepth = 1,
        int $resultDepth = 1,
        bool $additionalFormatting = false,
        bool $resultAsOneDimensionalArray = false,
        array $pathsToBigFoldersToSkipDeepSearchOn = [],
    ): array
    {
        $readableProjectRoot = $projectRoot === null ? \Safe\getcwd() : \Safe\realpath($projectRoot);
        if ($readableProjectRoot === '' || $readableProjectRoot === DIRECTORY_SEPARATOR) {
            // If it is supported, make sure you don't add the first directory separator whenever you parse deeper into it.
            throw new Exception('Currently we do not support putting the project at the direct root of an OS, this is a bad practice due to sensitive folders being there and it being hard to replicate in different testing environments');
        }

        $readablePathsToBigFoldersToSkipDeepSearchOn = [];
        foreach ($pathsToBigFoldersToSkipDeepSearchOn as $pathToBigFoldersToSkipDeepSearchOn) {
            $readablePathsToBigFoldersToSkipDeepSearchOn[] = \Safe\realpath($pathToBigFoldersToSkipDeepSearchOn);
        }

        $searchDepth = max($searchDepth, 1);
        $resultDepth = max($resultDepth, 1);

        $projectContents = self::search($readablePathsToBigFoldersToSkipDeepSearchOn, $readableProjectRoot, $searchDepth);
        self::processGitRulesFiles($projectContents, false);
        self::removeExcludedContent($projectContents);
        self::processGitRulesFiles($projectContents, true);
        self::removeExcludedContent($projectContents);
        return $resultAsOneDimensionalArray
            ? self::summarize1D($projectContents, $additionalFormatting, $resultDepth)
            : self::summarize2D($projectContents, $additionalFormatting, $resultDepth);
    }

    /**
     * @param array<int<0, max>, non-empty-string> $pathsToBigFoldersToSkipDeepSearchOn
     * @param non-empty-string $directoryPath
     * @param int<1, max> $maxDepth
     * @param int<1, max> $currentDepth
     * @param array<int<0, max>, non-empty-string> $route
     * @param string $localizedDirectoryPath
     * @return array<non-empty-string, mixed>
     * @throws DirException
     */
    private static function search(
        array $pathsToBigFoldersToSkipDeepSearchOn,
        string $directoryPath,
        int $maxDepth,
        int $currentDepth = 1,
        array $route = [],
        string $localizedDirectoryPath = ''
    ): array
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
            if ($isDir) {
                var_dump($path);
                var_dump($pathsToBigFoldersToSkipDeepSearchOn);
                var_dump(!in_array($path, $pathsToBigFoldersToSkipDeepSearchOn, true));
                var_dump($currentDepth < $maxDepth);
                $parsedContents[$fileOrFolderName]['contents'] = !in_array($path, $pathsToBigFoldersToSkipDeepSearchOn, true) && $currentDepth < $maxDepth
                    ? self::search($pathsToBigFoldersToSkipDeepSearchOn, $path, $maxDepth, $currentDepth + 1, $deeperRoute, $localizedPath)
                    : [];
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
     * @throws FilesystemException
     * @throws PcreException
     * @throws Exception
     */
    private static function processGitRulesFiles(array &$directory, bool $isSecondRoundForGitAttributes): void
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
                self::processRule($directory, $rule);
            }
        }
        foreach ($directory as $fileOrFolderName => $info) {
            if ($info['is_directory']) {
                // Do not replace $directory[$fileOrFolderName] with $info, we're trying to create a reference here.
                self::processGitRulesFiles($directory[$fileOrFolderName]['contents'], $isSecondRoundForGitAttributes);
            }
        }
    }

    /**
     * @param array<non-empty-string, mixed> $directory
     * @throws PcreException
     * @throws Exception
     */
    private static function processRule(array &$directory, PathMatcher $rule, string $localizedDirectoryPath = ''): void
    {
        // Rules can not look up, and they will always take the current directory of the .gitignore/.gitattributes file as their root.
        // Rules that counteract the rules before it will run as the new rule for the files/folders it applies to.
        if ($rule->targetsMatching()) {
            // TODO: Do the preg match and all "matches + is dir if dir targeting is on" will be marked as "not included"
        } else {
            // TODO: Do the preg match and all either "not matches" or "matches but is not directory while dir targeting" will be marked as "included"
            // TODO: Go from the root to the not matched file/folder to mark each of those directories as "included" to ensure an ignore plus include pattern will work, but you only have to do this once.
        }

        foreach ($directory as $fileOrFolderName => $info) {
            $localizedFileOrFolderPath = $localizedDirectoryPath === '' ? $fileOrFolderName : $localizedDirectoryPath . DIRECTORY_SEPARATOR . $fileOrFolderName;
            if (\Safe\preg_match('/'. $rule->asRegExp() . '/u', $localizedFileOrFolderPath, $matches)) {
                // You can have multiple matches, but not per single full path.
                if (count($matches) > 1) {
                    throw new Exception('Encountered more than one match for ' . $localizedFileOrFolderPath . ' through ' . $rule->asRegExp());
                }
                if ($rule->targetsOnlyDirectories() && !$info['is_directory']) {
                    //var_dump('-- SKIP - NOT DIR ------ ' . $rule->asRegExp() . ' => ' . $localizedFileOrFolderPath);
                    continue;
                }
                if ($info['is_directory']) {
                    // Do not replace $directory[$fileOrFolderName] with $info, we're trying to create a reference here.
                    self::processRule($directory[$fileOrFolderName]['contents'], $rule, $localizedFileOrFolderPath);
                }
                $directory[$fileOrFolderName]['included'] = false;
                //var_dump('-- MATCH --------------- ' . $rule->asRegExp() . ' => ' . $localizedFileOrFolderPath);
            }
        }
    }

    private static function summarize1D(array $directory, bool $showFolderOrFileType, int $maxDepth, int $currentDepth = 1): array
    {
        $contents = [];
        foreach ($directory as $fileOrFolderName => $info) {
            if ($info['is_directory']) {
                if (count($info['contents']) > 0 && $currentDepth < $maxDepth) {
                    $contents = array_merge($contents, self::summarize1D($info['contents'], $showFolderOrFileType, $maxDepth, $currentDepth + 1));
                } else {
                    if ($showFolderOrFileType) {
                        $contents[$info['localized_path'] . DIRECTORY_SEPARATOR] = 'folder';
                    } else {
                        $contents[] = $info['localized_path'] . DIRECTORY_SEPARATOR;
                    }
                }
            } else {
                if ($showFolderOrFileType) {
                    $contents[$info['localized_path']] = 'file';
                } else {
                    $contents[] = $info['localized_path'];
                }
            }
        }
        return $contents;
    }

    private static function summarize2D(array $directory, bool $showEmptyFoldersAsArray, int $maxDepth, int $currentDepth = 1): array
    {
        $contents = [];
        foreach ($directory as $fileOrFolderName => $info) {
            if ($info['is_directory']) {
                if (count($info['contents']) > 0 && $currentDepth < $maxDepth) {
                    $contents[$fileOrFolderName] = self::summarize2D($info['contents'], $showEmptyFoldersAsArray, $maxDepth, $currentDepth + 1);
                } else {
                    if ($showEmptyFoldersAsArray) {
                        $contents[$fileOrFolderName] = [];
                    } else {
                        $contents[] = $fileOrFolderName . DIRECTORY_SEPARATOR;
                    }
                }
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
                if ($info['is_directory']) {
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
