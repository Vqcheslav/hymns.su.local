<?php

namespace App\Dto;

use Stringable;

class ResultDto implements Stringable
{
    private bool $ok;

    private $data;

    private string $detail;

    private int $status;

    public function __construct(
        bool $ok,
        $data = [],
        string $detail = '',
        int $status = 200
    )
    {
        $this->status = $status;
        $this->detail = $detail;
        $this->data = $data;
        $this->ok = $ok;
    }

    public function hasErrors(): bool
    {
        return ! $this->ok;
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function setOk(bool $ok = true): self
    {
        $this->ok = $ok;

        return $this;
    }

    public function getData($key = null, $default = null)
    {
        if ($key !== null) {
            return $this->data[$key] ?? $default;
        }

        return $this->data;
    }

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function setDetail(string $detail): self
    {
        $this->detail = $detail;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'ok'     => $this->ok,
            'data'   => $this->data,
            'detail' => $this->detail,
            'status' => $this->status,
        ];
    }

    public function __toString(): string
    {
        return (string) json_encode($this->toArray());
    }
}
