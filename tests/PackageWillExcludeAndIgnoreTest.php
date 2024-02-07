<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\Tests;

use PHPUnit\Framework\TestCase;
use Rocky\PackageFiles\PackageParser;

final class PackageWillExcludeAndIgnoreTest extends TestCase
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
}