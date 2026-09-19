<?php

declare(strict_types=1);

namespace HrTech\Exceptions;

use Exception;
use JsonSerializable;
use Throwable;

/**
 * Root domain exception for HRTech Core.
 * Carries structured context metadata for logging and LGPD audit trails.
 */
class HrTechException extends Exception implements JsonSerializable
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        protected array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Adds contextual data and returns a clone.
     *
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): static
    {
        return new static(
            $this->getMessage(),
            $this->getCode(),
            $this->getPrevious(),
            array_merge($this->context, $context)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'exception' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
