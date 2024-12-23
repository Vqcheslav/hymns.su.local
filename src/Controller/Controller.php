<?php

namespace App\Controller;

use App\Dto\ResultDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class Controller extends AbstractController
{
    public function jsonResponseFromDto(ResultDto $dto): JsonResponse
    {
        return $this->jsonResponse($dto->isOk(), $dto->getData(), $dto->getDetail(), $dto->getStatus());
    }

    public function jsonResponse(bool $ok, $data, string $detail = '', int $status = 200): JsonResponse
    {
        return $this->json([
            'ok'     => $ok,
            'data'   => $data,
            'detail' => $detail,
            'status' => $status,
        ], $status);
    }
}
