<?php

declare(strict_types=1);

namespace HrTech\Database;

use HrTech\Contracts\SingletonInterface;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Class DatabaseManager
 *
 * High-performance SQLite PDO connection manager and schema orchestrator.
 * Implements Singleton pattern with full support for in-memory and persistent storage,
 * atomic transactions, foreign keys enforcement, and migration execution.
 *
 * @author Fernando Lopes Duarte (Relational Database Layer Lead)
 */
class DatabaseManager implements SingletonInterface
{
    private static ?self $instance = null;
    private PDO $pdo;
    private string $databasePath;

    /**
     * Protected constructor to prevent direct instantiation.
     */
    protected function __construct(string $databasePath)
    {
        $this->databasePath = $databasePath;
        $this->pdo = $this->createPdoConnection($databasePath);
    }

    /**
     * Prevent cloning.
     */
    protected function __clone()
    {
    }

    /**
     * Prevent unserialization.
     */
    public function __wakeup(): void
    {
        throw new RuntimeException('Cannot unserialize singleton DatabaseManager instance.');
    }

    /**
     * Returns or creates the singleton DatabaseManager instance.
     *
     * @param string|null $databasePath Optional path to SQLite file or ':memory:'
     * @return static
     */
    public static function getInstance(?string $databasePath = null): static
    {
        if (self::$instance === null) {
            $defaultPath = $databasePath ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database.sqlite';
            self::$instance = new self($defaultPath);
        } elseif ($databasePath !== null && self::$instance->getDatabasePath() !== $databasePath) {
            // Re-initialize if explicit different path requested (e.g. switching to :memory: for tests)
            self::$instance = new self($databasePath);
        }

        /** @var static */
        return self::$instance;
    }

    /**
     * Resets the singleton instance (useful for clean testing isolation).
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Creates and configures the PDO SQLite connection.
     */
    private function createPdoConnection(string $path): PDO
    {
        $dsn = "sqlite:{$path}";
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Enforce SQLite Foreign Keys and performance settings
        $pdo->exec('PRAGMA foreign_keys = ON;');
        if ($path !== ':memory:') {
            $pdo->exec('PRAGMA journal_mode = WAL;');
            $pdo->exec('PRAGMA synchronous = NORMAL;');
        }

        return $pdo;
    }

    /**
     * Returns the underlying PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /**
     * Returns the active database path.
     */
    public function getDatabasePath(): string
    {
        return $this->databasePath;
    }

    /**
     * Begins an atomic database transaction.
     */
    public function beginTransaction(): bool
    {
        if ($this->pdo->inTransaction()) {
            return false;
        }
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits the active transaction.
     */
    public function commit(): bool
    {
        if (!$this->pdo->inTransaction()) {
            return false;
        }
        return $this->pdo->commit();
    }

    /**
     * Rolls back the active transaction.
     */
    public function rollBack(): bool
    {
        if (!$this->pdo->inTransaction()) {
            return false;
        }
        return $this->pdo->rollBack();
    }

    /**
     * Checks if a transaction is currently active.
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Executes a callback within a managed transaction.
     * Automatically commits on success and rolls back on exception.
     *
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     * @throws Throwable
     */
    public function transaction(callable $callback): mixed
    {
        $alreadyInTransaction = $this->pdo->inTransaction();

        if (!$alreadyInTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $result = $callback($this->pdo);

            if (!$alreadyInTransaction && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if (!$alreadyInTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Creates all required relational tables and indexes if they do not already exist.
     */
    public function createTables(): void
    {
        $schema = [
            // 1. Tenants Table
            "CREATE TABLE IF NOT EXISTS tenants (
                id TEXT PRIMARY KEY NOT NULL,
                cnpj TEXT NOT NULL UNIQUE,
                corporate_name TEXT NOT NULL,
                trading_name TEXT NOT NULL,
                segment TEXT NOT NULL DEFAULT 'tech',
                is_active INTEGER NOT NULL DEFAULT 1,
                module_licenses TEXT NOT NULL DEFAULT '[]',
                created_at TEXT NOT NULL,
                updated_at TEXT
            );",

            // 2. Departments Table
            "CREATE TABLE IF NOT EXISTS departments (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                code TEXT NOT NULL,
                name TEXT NOT NULL,
                cost_center TEXT NOT NULL,
                manager_id TEXT,
                parent_department_id TEXT,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
            );",

            // 3. Roles Table
            "CREATE TABLE IF NOT EXISTS roles (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT,
                name TEXT NOT NULL,
                description TEXT NOT NULL DEFAULT '',
                hierarchy_level INTEGER NOT NULL DEFAULT 1,
                permissions TEXT NOT NULL DEFAULT '[]',
                department_id TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
            );",

            // 4. Employees Table
            "CREATE TABLE IF NOT EXISTS employees (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                cpf TEXT NOT NULL,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NOT NULL,
                birth_date TEXT NOT NULL,
                admission_date TEXT NOT NULL,
                termination_date TEXT,
                department_id TEXT NOT NULL,
                role_id TEXT NOT NULL,
                base_salary_cents INTEGER NOT NULL,
                employment_type TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                vacation_days_balance INTEGER NOT NULL DEFAULT 30,
                bank_hours_balance INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
            );",

            // 5. Users Table
            "CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                username TEXT NOT NULL,
                email TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                mfa_enabled INTEGER NOT NULL DEFAULT 0,
                employee_id TEXT,
                last_login_at TEXT,
                created_at TEXT NOT NULL,
                updated_at TEXT,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
            );",

            // 6. Time Logs Table (Portaria 671/2021 MTE)
            "CREATE TABLE IF NOT EXISTS time_logs (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                employee_id TEXT NOT NULL,
                timestamp TEXT NOT NULL,
                type TEXT NOT NULL,
                latitude REAL NOT NULL,
                longitude REAL NOT NULL,
                accuracy REAL,
                nsr INTEGER NOT NULL,
                previous_hash TEXT,
                signature_hash TEXT NOT NULL,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
            );",

            // 7. Time Adjustment Requests Table
            "CREATE TABLE IF NOT EXISTS time_adjustment_requests (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                employee_id TEXT NOT NULL,
                requested_date TEXT NOT NULL,
                original_time TEXT,
                requested_time TEXT NOT NULL,
                reason TEXT NOT NULL,
                attachment_path TEXT,
                status TEXT NOT NULL DEFAULT 'PENDING',
                approver_id TEXT,
                approved_at TEXT,
                review_comment TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
            );",

            // 8. Vacation Requests Table (CLT Art. 129-145)
            "CREATE TABLE IF NOT EXISTS vacation_requests (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                employee_id TEXT NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                duration_days INTEGER NOT NULL,
                abono_pecuniario INTEGER NOT NULL DEFAULT 0,
                advance_thirteenth_salary INTEGER NOT NULL DEFAULT 0,
                abono_days INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT 'REQUESTED',
                approver_id TEXT,
                approved_at TEXT,
                rejection_reason TEXT,
                created_at TEXT NOT NULL,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
            );",

            // 9. Benefits Table
            "CREATE TABLE IF NOT EXISTS benefits (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                type TEXT NOT NULL,
                name TEXT NOT NULL,
                provider TEXT NOT NULL,
                value_cents INTEGER NOT NULL,
                employee_cost_share_percentage REAL NOT NULL DEFAULT 0.0,
                is_deductible INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
            );",

            // 10. Equipment and ASO Table (NR-6 and NR-7)
            "CREATE TABLE IF NOT EXISTS equipment_aso (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                employee_id TEXT NOT NULL,
                equipment_name TEXT NOT NULL,
                ca_number TEXT NOT NULL,
                ca_expiration_date TEXT,
                delivery_date TEXT,
                return_date TEXT,
                exam_type TEXT NOT NULL DEFAULT 'PERIODIC',
                exam_date TEXT,
                expiration_date TEXT,
                physician_name TEXT,
                physician_crm TEXT,
                is_fit INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
            );",

            // 11. Insurance Policies Table (FinCorp Broker Portal)
            "CREATE TABLE IF NOT EXISTS insurance_policies (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                policy_number TEXT NOT NULL,
                broker_code TEXT NOT NULL,
                insurer_name TEXT NOT NULL,
                employee_id TEXT NOT NULL,
                insured_capital_cents INTEGER NOT NULL,
                monthly_premium_cents INTEGER NOT NULL,
                status TEXT NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                coverage_details TEXT NOT NULL DEFAULT '{}',
                cancellation_reason TEXT,
                cancelled_at TEXT,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
            );",

            // 12. Audit Logs Table (Tamper-evident cryptographic ledger)
            "CREATE TABLE IF NOT EXISTS audit_logs (
                id TEXT PRIMARY KEY NOT NULL,
                tenant_id TEXT NOT NULL,
                actor_user_id TEXT NOT NULL,
                action TEXT NOT NULL,
                entity_type TEXT NOT NULL,
                entity_id TEXT NOT NULL,
                previous_state TEXT NOT NULL DEFAULT '{}',
                new_state TEXT NOT NULL DEFAULT '{}',
                ip_address TEXT NOT NULL,
                user_agent TEXT NOT NULL,
                timestamp TEXT NOT NULL,
                previous_hash TEXT,
                integrity_hash TEXT NOT NULL,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
            );",

            // Indexes for Multi-Tenant performance and query isolation
            "CREATE INDEX IF NOT EXISTS idx_users_tenant ON users (tenant_id, username);",
            "CREATE INDEX IF NOT EXISTS idx_employees_tenant_cpf ON employees (tenant_id, cpf);",
            "CREATE INDEX IF NOT EXISTS idx_time_logs_tenant_emp ON time_logs (tenant_id, employee_id, timestamp);",
            "CREATE INDEX IF NOT EXISTS idx_audit_logs_tenant ON audit_logs (tenant_id, timestamp);",
            "CREATE INDEX IF NOT EXISTS idx_vacation_requests_emp ON vacation_requests (tenant_id, employee_id, status);",
            "CREATE INDEX IF NOT EXISTS idx_equipment_aso_emp ON equipment_aso (tenant_id, employee_id);",
            "CREATE INDEX IF NOT EXISTS idx_insurance_policies_emp ON insurance_policies (tenant_id, employee_id);",
            "CREATE INDEX IF NOT EXISTS idx_departments_tenant ON departments (tenant_id, code);",
            "CREATE INDEX IF NOT EXISTS idx_roles_tenant ON roles (tenant_id, hierarchy_level);",
            "CREATE INDEX IF NOT EXISTS idx_time_adjustment_requests_tenant_emp ON time_adjustment_requests (tenant_id, employee_id, status);",
            "CREATE INDEX IF NOT EXISTS idx_benefits_tenant ON benefits (tenant_id, type);"
        ];

        foreach ($schema as $sql) {
            $this->pdo->exec($sql);
        }
    }

    /**
     * Alias for createTables to adhere to standard migration naming conventions.
     */
    public function migrate(): void
    {
        $this->createTables();
    }

    /**
     * Drops all existing tables and re-runs migration.
     */
    public function resetDatabase(): void
    {
        $tables = [
            'audit_logs',
            'insurance_policies',
            'equipment_aso',
            'benefits',
            'vacation_requests',
            'time_adjustment_requests',
            'time_logs',
            'users',
            'employees',
            'roles',
            'departments',
            'tenants'
        ];

        $this->pdo->exec('PRAGMA foreign_keys = OFF;');
        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS {$table};");
        }
        $this->pdo->exec('PRAGMA foreign_keys = ON;');

        $this->createTables();
    }

    /**
     * Seeds initial test and baseline configuration data.
     *
     * @param array<string, mixed> $options
     */
    public function seed(array $options = []): void
    {
        $this->createTables();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM tenants WHERE id = :id");
        $stmt->execute(['id' => 'tenant-alpha']);
        if ((int)$stmt->fetchColumn() > 0) {
            return; // Already seeded
        }

        $now = date('Y-m-d\TH:i:sP');

        $this->transaction(function (PDO $pdo) use ($now) {
            // Seed sample Tenant (Tech segment)
            $stmt = $pdo->prepare("INSERT INTO tenants (id, cnpj, corporate_name, trading_name, segment, is_active, module_licenses, created_at, updated_at) 
                VALUES (:id, :cnpj, :corp, :trad, :seg, 1, :mods, :created, :updated)");
            $stmt->execute([
                'id' => 'tenant-alpha',
                'cnpj' => '11222333000181',
                'corp' => 'Alpha Software Ltd',
                'trad' => 'AlphaTech',
                'seg' => 'tech',
                'mods' => json_encode(['time_tracking', 'payroll', 'benefits', 'insurance']),
                'created' => $now,
                'updated' => $now
            ]);

            // Seed sample Department
            $stmt = $pdo->prepare("INSERT INTO departments (id, tenant_id, code, name, cost_center, is_active, created_at, updated_at)
                VALUES (:id, :tenant_id, :code, :name, :cost_center, 1, :created, :updated)");
            $stmt->execute([
                'id' => 'dept-eng',
                'tenant_id' => 'tenant-alpha',
                'code' => 'ENG-01',
                'name' => 'Engineering',
                'cost_center' => 'CC-1000',
                'created' => $now,
                'updated' => $now
            ]);

            // Seed sample Role
            $stmt = $pdo->prepare("INSERT INTO roles (id, tenant_id, name, description, hierarchy_level, permissions, created_at, updated_at)
                VALUES (:id, :tenant_id, :name, :desc, :level, :perms, :created, :updated)");
            $stmt->execute([
                'id' => 'role-dev',
                'tenant_id' => 'tenant-alpha',
                'name' => 'Senior Developer',
                'desc' => 'Core Platform Engineer',
                'level' => 50,
                'perms' => json_encode(['time.punch', 'vacation.request', 'benefits.view']),
                'created' => $now,
                'updated' => $now
            ]);
        });
    }
}
