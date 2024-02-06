### Introduction
This little script can be used to quickly see what will be included when you turn your project into a Composer package and release a new version.
Only in PHP, as I only work in that, but it's so small that you're free to replicate it.

### History
This was designed when making [Faker](https://github.com/Rockylars/Faker) an open source package, as I didn't see anyone make this before.
It's simple and quite handy, just tells you what will go in, though only cares about top level searches at the moment.

### Usage
Simply call this from your a test you put down in your Unit tests folder, or just the tests folder if you only got that.

### Future
I plan to upgrade this to eventually do all the magical and recursive depth searches, but that's usually far out of scope for my projects anyways.

### Examples
```php
self::assertSame(
    [
        'LICENSE',
        'README.md',
        'composer.json',
        'src'
    ],
    PackageParser::simplePackageSearch(__DIR__ . 'path to the root')
);
```