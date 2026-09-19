<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;

class AuthController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function switchTenant(): void {
        $role = $_GET['role'] ?? 'tenant-tech';
        $_SESSION['active_role'] = $role;
        $_SESSION['flash_message'] = "Papel do sistema alterado com sucesso para: " . strtoupper($role);
        
        logQuery("SESSION UPDATE active_role = '$role'", [], "Singleton TenantContextManager");

        $redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php?route=dashboard';
        header("Location: $redirect");
        exit;
    }
}
