<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DomainException extends Exception
{
    protected string $errorCode = 'DOMAIN_ERROR';
    protected int $statusCode = Response::HTTP_BAD_REQUEST;

    public function __construct(string $message = '', string $errorCode = 'DOMAIN_ERROR', int $statusCode = Response::HTTP_BAD_REQUEST)
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error(
            message: $this->getMessage(),
            errorCode: $this->errorCode,
            status: $this->statusCode
        );
    }
}
