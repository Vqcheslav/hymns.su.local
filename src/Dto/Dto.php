<?php

namespace App\Dto;

use Stringable;

abstract class Dto implements Stringable
{
    public function toArray(): array
    {
        $result = [];

        foreach ($this as $key => $value) {
            $result[$key] = $value;
        }

        return $result;
    }

    public function toJson(): string
    {
        return (string) json_encode($this->toArray());
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
