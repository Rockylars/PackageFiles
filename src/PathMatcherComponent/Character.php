<?php

declare(strict_types=1);

namespace Rocky\PackageFiles\PathMatcherComponent;

use Rocky\PackageFiles\PathMatcherComponent;

final class Character implements PathMatcherComponent
{
    public function __construct(
        private string $character
    ) {}

    /** @inheritDoc */
    public function asRegExp(): string
    {
        // If it is a reserved character, an operator in RegExp.
        if (in_array($this->character, ['^', '$', '.', '|', '(', ')', '[', ']', '{', '}', '*', '+', '?', '/', '\\'])) {
            // \*
            return self::REG_EXP_ESCAPE . $this->character;
        } else {
            // a
            return $this->character;
        }
    }
}
