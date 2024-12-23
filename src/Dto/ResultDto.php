<?php

namespace App\Dto;

class ResultDto
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

    public function setOk(bool $ok): ResultDto
    {
        $this->ok = $ok;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): ResultDto
    {
        $this->data = $data;

        return $this;
    }

    public function getDetail(): string
    {
        return $this->detail;
    }

    public function setDetail(string $detail): ResultDto
    {
        $this->detail = $detail;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): ResultDto
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