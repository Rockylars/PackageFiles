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
     * @return array<int, string>
     *
     * @throws Exception <br> > if $projectRoot is not a directory
     * @throws FilesystemException <br> > if the root .gitignore file can not be found <br> > if the .gitattributes file can not be found
     * @throws DirException <br> > if $projectRoot is not a directory
     * @throws PcreException <br> > if the regex breaks
     */
    public static function simplePackageSearch(string $projectRoot): array
    {
        $filesOrFoldersExcluded = ['.', '..', '.git'];

        if (!is_dir($projectRoot)) {
            throw new Exception('"' . $projectRoot . '" is not a directory');
        }

        $gitIgnore = \Safe\file_get_contents($projectRoot . DIRECTORY_SEPARATOR . '.gitignore');
        self::matchFileContents($gitIgnore, '/^\/(.*?)\/?$/', $filesOrFoldersExcluded);

        $gitAttributes = \Safe\file_get_contents($projectRoot . DIRECTORY_SEPARATOR . '.gitattributes');
        self::matchFileContents($gitAttributes, '/^\/(.*?)\/? *export-ignore$/', $filesOrFoldersExcluded);

        /** @var array<int, string> $project */
        $project = \Safe\scandir($projectRoot);

        $result = [];
        foreach ($project as $projectContents) {
            if (!in_array($projectContents, $filesOrFoldersExcluded, true)) {
                $result[] = $projectContents;
            }
        }
        return $result;
    }

    /**
     * @param string $fileContents
     * @param string $matcher
     * @param array<int, string> $filesOrFoldersExcluded
     * @return void
     * @throws PcreException <br> > if the regex breaks
     */
    private static function matchFileContents(string $fileContents, string $matcher, array &$filesOrFoldersExcluded): void
    {
        $lines = explode("\n", $fileContents);
        foreach ($lines as $line) {
            $matches = [];
            if (\Safe\preg_match($matcher, $line, $matches)) {
                $filesOrFoldersExcluded[] = $matches[1];
            }
        }
    }
}
