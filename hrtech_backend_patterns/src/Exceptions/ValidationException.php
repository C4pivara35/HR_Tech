<?php

declare(strict_types=1);

namespace HrTech\Exceptions;

use Throwable;

/**
 * Thrown when domain or input data validation invariants are violated.
 */
class ValidationException extends HrTechException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        string $message = 'Validation failed',
        private readonly array $errors = [],
        int $code = 422,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, array_merge($context, ['errors' => $errors]));
    }

    /**
     * Factory for a single field validation failure.
     */
    public static function forField(string $field, string $error): static
    {
        return new static(
            "Validation failed for '{$field}': {$error}",
            [$field => [$error]]
        );
    }

    /**
     * Factory for multiple field validation failures.
     *
     * @param array<string, mixed> $errors
     */
    public static function withErrors(array $errors, string $message = 'Validation failed'): static
    {
        return new static($message, $errors);
    }

    /**
     * Enriches validation exception with additional context without parameter misalignment.
     *
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): static
    {
        $mergedContext = array_merge($this->context, $context);
        return new static(
            $this->getMessage(),
            $this->errors,
            $this->getCode(),
            $this->getPrevious(),
            $mergedContext
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Returns the first error message, either for a specific field or overall.
     */
    public function getFirstError(?string $field = null): ?string
    {
        if ($field !== null) {
            if (!isset($this->errors[$field])) {
                return null;
            }
            $fieldErrors = (array)$this->errors[$field];
            return $fieldErrors[0] ?? null;
        }

        foreach ($this->errors as $fieldErrors) {
            $list = (array)$fieldErrors;
            if (!empty($list)) {
                return $list[0];
            }
        }

        return $this->getMessage();
    }
}
