<?php
declare(strict_types=1);

namespace App\Helper;

final class NonEmptyStringHelper
{
    public static function valid(?string $value): bool
    {
        return \is_string($value) && $value !== '';
    }
}