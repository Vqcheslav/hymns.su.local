<?php

namespace App\Service;

use App\Dto\ResultDto;
use Throwable;

abstract class Service
{
    public function makeResultDto(bool $ok, $data = [], string $detail = '', int $status = 200): ResultDto
    {
        return new ResultDto($ok, $data, $detail, $status);
    }

    public function jsonEncode($data, int $flags = 0, int $depth = 512): ResultDto
    {
        try {
            $data = json_encode($data, JSON_THROW_ON_ERROR | $flags, $depth);
        } catch (Throwable $e) {
            return $this->makeResultDto(false, $e, $e->getMessage());
        }

        return $this->makeResultDto(true, $data);
    }

    public function jsonDecode($data, bool $isAssociative = true, int $depth = 512, int $flags = 0): ResultDto
    {
        try {
            $data = json_decode($data, $isAssociative, $depth, JSON_THROW_ON_ERROR | $flags);
        } catch (Throwable $e) {
            return $this->makeResultDto(false, $e, $e->getMessage());
        }

        return $this->makeResultDto(true, $data);
    }

    public function objectToArray($data): ResultDto
    {
        $encodedResultDto = $this->jsonEncode($data);

        if ($encodedResultDto->hasErrors()) {
            return $encodedResultDto;
        }

        return $this->jsonDecode($encodedResultDto->getData());
    }
}
