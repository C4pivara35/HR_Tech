<?php

declare(strict_types=1);

namespace HrTech\Domain\Enums;

/**
 * Access control roles within HRTech Core.
 */
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case TENANT_ADMIN = 'tenant_admin';
    case HR_MANAGER = 'hr_manager';
    case DEPARTMENT_MANAGER = 'department_manager';
    case EMPLOYEE = 'employee';
    case AUDITOR = 'auditor';
    case BROKER = 'broker';

    /**
     * Human-readable label in Portuguese.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrador',
            self::TENANT_ADMIN => 'Administrador da Empresa',
            self::HR_MANAGER => 'Gestor de Recursos Humanos',
            self::DEPARTMENT_MANAGER => 'Gestor de Departamento',
            self::EMPLOYEE => 'Colaborador',
            self::AUDITOR => 'Auditor de Conformidade e LGPD',
            self::BROKER => 'Corretor de Seguros (Portal FinCorp)',
        };
    }

    /**
     * Role privilege hierarchy level (higher value = higher authority).
     */
    public function hierarchyLevel(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 100,
            self::TENANT_ADMIN => 80,
            self::HR_MANAGER => 60,
            self::DEPARTMENT_MANAGER => 40,
            self::AUDITOR => 30,
            self::BROKER => 20,
            self::EMPLOYEE => 10,
        };
    }

    /**
     * Checks if this role has authority over another role.
     */
    public function canManage(UserRole $targetRole): bool
    {
        return $this->hierarchyLevel() > $targetRole->hierarchyLevel();
    }

    /**
     * Indicates whether the role has administrative access.
     */
    public function isAdministrative(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::TENANT_ADMIN, self::HR_MANAGER], true);
    }

    /**
     * Indicates whether the role has HR management authority.
     */
    public function isHr(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::TENANT_ADMIN, self::HR_MANAGER], true);
    }

    /**
     * Indicates whether the role is an external user (e.g. insurance broker).
     */
    public function isExternal(): bool
    {
        return $this === self::BROKER;
    }

    /**
     * Returns an array of all scalar values.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Returns an associative array of [value => label].
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
