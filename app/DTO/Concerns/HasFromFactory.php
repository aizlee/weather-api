<?php

namespace App\DTO\Concerns;

trait HasFromFactory
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function from(array $attributes): static
    {
        return new static(...$attributes);
    }
}
