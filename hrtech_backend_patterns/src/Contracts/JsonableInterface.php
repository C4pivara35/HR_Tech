<?php

declare(strict_types=1);

namespace HrTech\Contracts;

/**
 * Interface JsonableInterface
 *
 * Contract for objects capable of serializing directly to JSON.
 */
interface JsonableInterface
{
    /**
     * Converte o objeto para sua representação JSON.
     *
     * @param int $options Bitmask of JSON_* flags (defaults to unescaped slashes and uúnicode).
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): string;
}
