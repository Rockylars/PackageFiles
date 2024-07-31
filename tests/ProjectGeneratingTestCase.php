<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;

// TODO: When switching to PHP 8.1, make the constant final.
// TODO: When switching to PHP 8.2, turn this into a trait.
abstract class ProjectGeneratingTestCase extends TestCase
{
    protected const TEST_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'fake_project';

    /**
     * @param array<string, mixed> $fileStructure
     * @throws FilesystemException
     */
    final protected static function createFileStructure(array $fileStructure, bool $allowBackslash = false, string $directoryPath = self::TEST_DIRECTORY): void
    {
        if ($directoryPath === self::TEST_DIRECTORY) {
            \Safe\mkdir(self::TEST_DIRECTORY);
        }

        // Unfortunately, I can only check this after creating the first folder.
        if (!str_starts_with($readablePath = \Safe\realpath($directoryPath), self::TEST_DIRECTORY)) {
            throw new Exception('Can not alter potentially dangerous path: ' . $readablePath);
        }

        /**
         * @var string $fileOrFolderName
         * @var string|array<string, mixed> $contents
         */
        foreach ($fileStructure as $fileOrFolderName => $contents) {
            if (str_contains($fileOrFolderName, '/') || (!$allowBackslash && str_contains($fileOrFolderName, '\\'))) {
                throw new Exception('Can not create file/directory with a (back)slash in the name');
            }
            $fileOrFolderPath = $directoryPath . DIRECTORY_SEPARATOR . $fileOrFolderName;
            if (is_array($contents)) {
                \Safe\mkdir($fileOrFolderPath);
                self::createFileStructure($contents, $allowBackslash, $fileOrFolderPath);
            } else {
                \Safe\file_put_contents($fileOrFolderPath, $contents);
            }
        }
    }

    /**
     * Has to go through the whole structure since PHP won't delete filled folders, this is safer anyway.
     * @throws FilesystemException
     * @throws DirException
     */
    final protected static function removeFileStructure(string $directoryPath = self::TEST_DIRECTORY): void
    {
        if (!str_starts_with($readablePath = \Safe\realpath($directoryPath), self::TEST_DIRECTORY)) {
            throw new Exception('Can not alter potentially dangerous path: ' . $readablePath);
        }
        $contents = \Safe\scandir($directoryPath);
        $contentCount = count($contents);
        for ($i = 0; $i < $contentCount; $i++) {
            $fileOrFolderName = $contents[$i];
            if (in_array($fileOrFolderName, ['.', '..'], true)) {
                continue;
            }
            if (is_dir($fileOrFolderPath = $directoryPath . DIRECTORY_SEPARATOR . $fileOrFolderName)) {
                self::removeFileStructure($fileOrFolderPath);
            } else {
                \Safe\unlink($fileOrFolderPath);
            }
        }
        \Safe\rmdir($directoryPath);
    }
}