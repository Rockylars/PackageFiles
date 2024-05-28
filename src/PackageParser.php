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
        $projectContents = self::search($projectRoot, 1, $searchDepth);
        self::processGitIgnoreFiles($projectContents);
        //TODO: Remove these below later.
        $projectContents['.idea']['included'] = false;
        $projectContents['output']['included'] = false;
        $projectContents['vendor']['included'] = false;
        self::removeExcludedContent($projectContents);
        self::processGitAttributesFiles($projectContents);
        self::removeExcludedContent($projectContents);
        return self::summarize($projectContents, 1, $resultDepth);
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
                $rule = RuleParser::run($line, false);
                if ($rule === null) {
                    continue;
                }
                self::processRule($directory, $rule);
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

    private static function processGitAttributesFiles(array &$directory): void
    {
        if (array_key_exists('.gitattributes', $directory)) {
            $lines = explode("\n", \Safe\file_get_contents($directory['.gitattributes']['path']));
            foreach ($lines as $line) {
                $rule = RuleParser::run($line, true);
                if ($rule === null) {
                    continue;
                }
                self::processRule($directory, $rule);
            }
        }
        foreach ($directory as $fileOrFolderName => $info) {
            // The search depth will make some directories not fetch their contents.
            if ($info['is_directory'] && isset($info['contents'])) {
                // Do not replace $directory[$fileOrFolderName] with $info, we're trying to create a reference here.
                self::processGitAttributesFiles($directory[$fileOrFolderName]['contents']);
            }
        }
    }

    private static function processRule(array &$directory, IgnoreRule $rule): void
    {
        // Rules can not look up, and they will always take the current directory of the .gitignore/.gitattributes file as their root.
        // Rules that counteract the rules before it will run as the new rule for the files/folders it applies to.
        foreach ($directory as $fileOrFolderName => $info) {
            // The search depth will make some directories not fetch their contents.
            if (/** TODO: If rule influences more layers */ $info['is_directory'] && isset($info['contents'])) {
                // Do not replace $directory[$fileOrFolderName] with $info, we're trying to create a reference here.
                self::processRule($directory[$fileOrFolderName]['contents'], $rule);
            }
            //TODO: Add a rule localizer for rules that influence deeper paths specifically and not all paths.
        }
    }

    private static function summarize(array $directory, int $currentDepth, int $maxDepth): array
    {
        $contents = [];
        foreach ($directory as $fileOrFolderName => $info) {
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
