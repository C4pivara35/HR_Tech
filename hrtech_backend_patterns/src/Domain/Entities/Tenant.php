<?php

declare(strict_types=1);

namespace HrTech\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use HrTech\Contracts\ArrayableInterface;
use HrTech\Contracts\AuditableInterface;
use HrTech\Contracts\IdentifiableInterface;
use HrTech\Contracts\JsonableInterface;
use HrTech\Contracts\StringableInterface;
use HrTech\Contracts\ValidatableInterface;
use HrTech\Domain\ValueObjects\Cnpj;
use HrTech\Exceptions\ValidationException;
use JsonSerializable;

/**
 * Class Tenant
 *
 * Represents a multi-tenant client enterprise within HRTech Core.
 * Acts as the top-level isolation container for all organizational domain data.
 */
class Tenant implements
    IdentifiableInterface,
    ValidatableInterface,
    ArrayableInterface,
    JsonableInterface,
    AuditableInterface,
    StringableInterface,
    JsonSerializable
{
    private string $id;
    private Cnpj $cnpj;
    private string $corporateName;
    private string $tradingName;
    private string $segment;
    private bool $isActive;
    /** @var array<int, string> */
    private array $moduleLicenses;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    /**
     * @param string $id Unique tenant identifier.
     * @param Cnpj|string $cnpj CNPJ Value Object or digits.
     * @param string $corporateName Razão Social.
     * @param string $tradingName Nome Fantasia.
     * @param bool|array<int, string> $isActive Operational status or module array if positional.
     * @param array<int, string>|bool $moduleLicenses Licensed feature modules.
     * @param DateTimeImmutable|string|null $createdAt Creation timestamp.
     * @param string $segment Market segment profile (tech, industria, financeiro).
     * @throws ValidationException If validation invariants are violated.
     */
    public function __construct(
        string $id,
        Cnpj|string $cnpj,
        string $corporateName = '',
        string $tradingName = '',
        bool|array $isActive = true,
        array|bool $moduleLicenses = [],
        DateTimeImmutable|string|null $createdAt = null,
        string $segment = 'tech'
    ) {
        $this->id = trim($id);
        $this->cnpj = is_string($cnpj) ? new Cnpj($cnpj) : $cnpj;
        $this->corporateName = trim($corporateName);
        $this->tradingName = trim($tradingName);
        $this->segment = trim($segment) !== '' ? trim($segment) : 'tech';

        // Allow flexible positional ordering of isActive and moduleLicenses
        if (is_array($isActive)) {
            $rawModules = $isActive;
            $this->isActive = is_bool($moduleLicenses) ? $moduleLicenses : true;
        } else {
            $this->isActive = $isActive;
            $rawModules = is_array($moduleLicenses) ? $moduleLicenses : [];
        }

        $this->moduleLicenses = $this->normalizeModules($rawModules);

        if ($createdAt === null) {
            $this->createdAt = new DateTimeImmutable();
        } elseif (is_string($createdAt)) {
            $this->createdAt = new DateTimeImmutable($createdAt);
        } else {
            $this->createdAt = $createdAt;
        }

        $this->updatedAt = $this->createdAt;
        $this->validate();
    }

    /**
     * Named factory for creating a new active tenant.
     *
     * @param string $id
     * @param Cnpj|string $cnpj
     * @param string $corporateName
     * @param string $tradingName
     * @param array<int, string> $modules
     * @return self
     */
    public static function create(
        string $id,
        Cnpj|string $cnpj,
        string $corporateName,
        string $tradingName,
        array $modules = []
    ): self {
        return new self($id, $cnpj, $corporateName, $tradingName, true, $modules);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCnpj(): Cnpj
    {
        return $this->cnpj;
    }

    public function getCorporateName(): string
    {
        return $this->corporateName;
    }

    public function getTradingName(): string
    {
        return $this->tradingName;
    }

    public function getSegment(): string
    {
        return $this->segment;
    }

    public function setSegment(string $segment): void
    {
        $this->segment = trim($segment) !== '' ? trim($segment) : 'tech';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return array<int, string>
     */
    public function getModuleLicenses(): array
    {
        return $this->moduleLicenses;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Checks if a specific feature module is licensed.
     */
    public function hasModule(string $module): bool
    {
        $normalized = strtolower(trim($module));
        return in_array($normalized, $this->moduleLicenses, true);
    }

    /**
     * Licenses a new feature module for this tenant.
     */
    public function addModule(string $module): void
    {
        $normalized = strtolower(trim($module));
        if ($normalized === '') {
            throw ValidationException::forField('module', 'Module identifier cannot be empty.');
        }

        if (!in_array($normalized, $this->moduleLicenses, true)) {
            $this->moduleLicenses[] = $normalized;
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    /**
     * Revokes a licensed module from this tenant.
     */
    public function removeModule(string $module): void
    {
        $normalized = strtolower(trim($module));
        $key = array_search($normalized, $this->moduleLicenses, true);
        if ($key !== false) {
            unset($this->moduleLicenses[$key]);
            $this->moduleLicenses = array_values($this->moduleLicenses);
            $this->updatedAt = new DateTimeImmutable();
        }
    }

    /**
     * Replaces the entire module license portfolio.
     *
     * @param array<int, string> $modules
     */
    public function setModuleLicenses(array $modules): void
    {
        $this->moduleLicenses = $this->normalizeModules($modules);
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Activates the tenant account.
     */
    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Deactivates the tenant account, suspending access for all associated users.
     */
    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Updates corporate and trading names.
     */
    public function updateNames(string $corporateName, string $tradingName): void
    {
        $corp = trim($corporateName);
        $trad = trim($tradingName);

        if ($corp === '') {
            throw ValidationException::forField('corporate_name', 'Corporate name cannot be empty.');
        }
        if ($trad === '') {
            throw ValidationException::forField('trading_name', 'Trading name cannot be empty.');
        }

        $this->corporateName = $corp;
        $this->tradingName = $trad;
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @throws ValidationException
     */
    public function validate(): void
    {
        $errors = [];

        if ($this->id === '') {
            $errors['id'][] = 'Tenant ID cannot be empty.';
        }

        if ($this->corporateName === '') {
            $errors['corporate_name'][] = 'Corporate name (Razão Social) cannot be empty.';
        } elseif (mb_strlen($this->corporateName) < 2) {
            $errors['corporate_name'][] = 'Corporate name must contain at least 2 characters.';
        }

        if ($this->tradingName === '') {
            $errors['trading_name'][] = 'Trading name (Nome Fantasia) cannot be empty.';
        } elseif (mb_strlen($this->tradingName) < 2) {
            $errors['trading_name'][] = 'Trading name must contain at least 2 characters.';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors, 'Tenant validation failed.');
        }
    }

    public function isValid(): bool
    {
        try {
            $this->validate();
            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cnpj' => $this->cnpj->getValue(),
            'cnpj_formatted' => $this->cnpj->getFormatted(),
            'corporate_name' => $this->corporateName,
            'trading_name' => $this->tradingName,
            'segment' => $this->segment,
            'is_active' => $this->isActive,
            'module_licenses' => $this->moduleLicenses,
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(DateTimeInterface::ATOM),
        ];
    }

    public function toJson(int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getAuditIdentifier(): string
    {
        return $this->id;
    }

    public function getAuditCategory(): string
    {
        return 'Tenant';
    }

    public function toAuditArray(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return sprintf(
            'Tenant[%s] %s (%s)',
            $this->id,
            $this->tradingName,
            $this->cnpj->getFormatted()
        );
    }

    /**
     * @param array<int, mixed> $modules
     * @return array<int, string>
     */
    private function normalizeModules(array $modules): array
    {
        $normalized = [];
        foreach ($modules as $m) {
            $clean = strtolower(trim((string)$m));
            if ($clean !== '') {
                $normalized[$clean] = $clean;
            }
        }
        return array_values($normalized);
    }
}
