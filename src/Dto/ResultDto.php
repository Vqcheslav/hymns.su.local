<?php

namespace App\Dto;

class ResultDto extends Dto
{
    private bool $ok;

    private mixed $data;

    private string $detail;

    private int $status;

    public function __construct(
        bool $ok,
        mixed $data = [],
        string $detail = '',
        int $status = 200,
    ) {
        $this->ok = $ok;
        $this->data = $data;
        $this->detail = $detail;
        $this->status = $status;
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
}
