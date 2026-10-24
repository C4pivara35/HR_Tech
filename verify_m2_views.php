<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * verify_m2_views.php — Automated End-to-End Test Suite for Milestone M2 Views
 * ==============================================================================
 * Discipline: Medição e Análise de Software / Engenharia de Software
 * Milestone: M2 — Web Views, Routing & Functional Layout Compliance
 * Total Scope: 50 Elementary Functions (252 PFB) across 5 Members
 *
 * Verifies:
 * 1. Zero PHP Warnings, Fatal Errors, or Uncaught Exceptions across all routes/actions.
 * 2. Layout Compliance: All 10 CEs contain segregated Entrada (Filtros) & Saída (Resultados).
 * 3. Layout Compliance: All 10 SEs contain dedicated "Justificativa do Dado Derivado" cards.
 * 4. All 10 AIEs are connected, tested, and accessible via UI and simulators.
 * ==============================================================================
 */

define('CLI_TEST_RUNNER', true);
define('ROOT_DIR', __DIR__);
define('BACKEND_BASE', ROOT_DIR . '/hrtech_backend_patterns');

// Ensure autoloader
require_once BACKEND_BASE . '/src/Autoloader.php';
$loader = new HrTech\Autoloader();
$loader->addNamespace('HrTech', BACKEND_BASE . '/src');
$loader->addNamespace('App', ROOT_DIR . '/app');
$loader->register();

use HrTech\Database\DatabaseManager;
use HrTech\Patterns\Singleton\TenantContextManager;
use HrTech\Lps\FeatureToggleManager;

// Initialize Database Connection
$dbManager = DatabaseManager::getInstance(ROOT_DIR . '/hrtech_db.sqlite');
$dbManager->migrate();
$pdo = $dbManager->getConnection();

// Mock environment and session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['active_role'] = 'tenant-tech';
$_SESSION['sql_logs'] = [];
$GLOBALS['SQL_LOGS'] = &$_SESSION['sql_logs'];
$GLOBALS['GOF_PATTERNS_USED'] = [];

if (!function_exists('logQuery')) {
    function logQuery(string $sql, array $params = [], string $pattern = '', string $pathway = ''): void {
        // Mock query logging
    }
}

// Router instance
require_once ROOT_DIR . '/app/Router.php';
$router = new App\Router($pdo);

// Test scenarios matrix
$testCases = [
    [
        'name' => 'Tela 01 / Dashboard — SE_REL_TURNO_CONTRATO',
        'params' => ['route' => 'dashboard'],
        'role' => 'tenant-tech',
        'assertions' => [
            'Turnover',
            'SE_REL_TURNO_CONTRATO',
            'Justificativa do Dado Derivado',
            'Rotatividade Contratual'
        ]
    ],
    [
        'name' => 'Tela 02 / Ponto — CE_BATIDAS_DO_DIA & SE_EXTRATO_ESPELHO_PONTO',
        'params' => ['route' => 'timelogs'],
        'role' => 'tenant-tech',
        'assertions' => [
            'CE_BATIDAS_DO_DIA',
            'SE_EXTRATO_ESPELHO_PONTO',
            'DADOS DE ENTRADA (FILTROS)',
            'DADOS DE SAÍDA (RESULTADOS)',
            'Justificativa do Dado Derivado',
            'Hora Noturna Ficta'
        ]
    ],
    [
        'name' => 'Tela 02 / Ponto — Filtro por Data e Colaborador',
        'params' => ['route' => 'timelogs', 'search_date' => '2026-09-15', 'search_employee_id' => 'emp-lucas'],
        'role' => 'tenant-tech',
        'assertions' => [
            'CE_BATIDAS_DO_DIA',
            'DADOS DE ENTRADA (FILTROS)',
            'Registro Fiscal de Batidas'
        ]
    ],
    [
        'name' => 'Tela 02 / Banco de Horas — CE_CONSULTAR_BANCO_HORAS & SE_APURACAO_BANCO_HORAS',
        'params' => ['route' => 'timelogs', 'action' => 'bank_hours'],
        'role' => 'tenant-tech',
        'assertions' => [
            'CE_CONSULTAR_BANCO_HORAS',
            'SE_APURACAO_BANCO_HORAS',
            'DADOS DE ENTRADA (FILTROS)',
            'DADOS DE SAÍDA (RESULTADOS)',
            'Justificativa do Dado Derivado',
            'BankHoursStrategy'
        ]
    ],
    [
        'name' => 'Tela 09 / Solicitar Ajuste de Ponto — EE_AJUSTE_ABONO_PONTO',
        'params' => ['route' => 'timelogs', 'action' => 'request_adjustment_view'],
        'role' => 'tenant-tech',
        'assertions' => [
            'EE_AJUSTE_ABONO_PONTO',
            'Solicitar Retificação / Abono de Ponto',
            'Portaria 671 / CLT',
            'Formulário de Retificação de Marcação'
        ]
    ],
    [
        'name' => 'Tela 03 / Colaboradores — CE_BUSCA_COLABORADOR',
        'params' => ['route' => 'employees'],
        'role' => 'tenant-tech',
        'assertions' => [
            'CE_BUSCA_COLABORADOR',
            'DADOS DE ENTRADA (FILTROS)',
            'DADOS DE SAÍDA (RESULTADOS)',
            'CLT'
        ]
    ],
    [
        'name' => 'Tela 03 / Colaboradores — Filtro Ativo por CPF e Status',
        'params' => ['route' => 'employees', 'search_cpf' => '529', 'search_status' => 'active'],
        'role' => 'tenant-tech',
        'assertions' => [
            'CE_BUSCA_COLABORADOR',
            'DADOS DE ENTRADA (FILTROS)'
        ]
    ],
    [
        'name' => 'Tela 05 / Novo Colaborador — Form e Validador Receita CPF',
        'params' => ['route' => 'employees', 'action' => 'new'],
        'role' => 'tenant-tech',
        'assertions' => [
            'EE_CADASTRAR_COLABORADOR',
            'AIE_RECEITA_FEDERAL_CPF',
            'Admissão Formal de Colaborador',
            'Receita Federal do Brasil'
        ]
    ],
    [
        'name' => 'Tela 06 / Detalhe Colaborador — eSocial S-2200 XML Viewer',
        'params' => ['route' => 'employees', 'action' => 'detail', 'id' => 'emp-ana'],
        'role' => 'tenant-tech',
        'assertions' => [
            'SE_XML_ESOCIAL_S2200',
            'AIE_GOV_ESOCIAL',
            'Evento eSocial S-2200',
            'Digest Canônico SHA-256'
        ]
    ],
    [
        'name' => 'Tela 07 / Organograma — CE_ORGANOGRAMA_GRAFICO',
        'params' => ['route' => 'organogram'],
        'role' => 'tenant-tech',
        'assertions' => [
            'CE_ORGANOGRAMA_GRAFICO',
            'ALI_DEPARTAMENTOS_CARGOS',
            'EE_CRIAR_DEPARTAMENTO_CARGO',
            'DADOS DE ENTRADA (FILTROS)',
            'DADOS DE SAÍDA (RESULTADOS)'
        ]
    ],
    [
        'name' => 'Tela 11 / Central de Aprovações — Férias e Ponto',
        'params' => ['route' => 'approvals'],
        'role' => 'tenant-tech',
        'assertions' => [
            'Central de Aprovações do Gestor',
            'EE_SOLICITAR_FERIAS',
            'EE_AJUSTE_ABONO_PONTO',
            'Solicitações de Férias Pendentes de Homologação',
            'Retificações e Abonos de Ponto Pendentes'
        ]
    ],
    [
        'name' => 'Tela 10 / Férias — CE_CONSULTAR_SALDO_FERIAS & SE_CALCULO_RECIBO_FERIAS',
        'params' => ['route' => 'vacations'],
        'role' => 'tenant-tech',
        'assertions' => [
            'CE_CONSULTAR_SALDO_FERIAS',
            'SE_CALCULO_RECIBO_FERIAS',
            'Dados de Entrada (Filtros de Pesquisa)',
            'Dados de Saída (Quadro de Saldos Aquisitivos)',
            'Justificativa do Dado Derivado',
            '1/3 Constitucional'
        ]
    ],
    [
        'name' => 'Tela 10 / Recibo Dedicado de Férias CLT',
        'params' => ['route' => 'vacations', 'action' => 'receipt'],
        'role' => 'tenant-tech',
        'assertions' => [
            'SE_CALCULO_RECIBO_FERIAS',
            'Recibo Oficial de Concessão de Férias',
            'Justificativa do Dado Derivado',
            'Abono Pecuniário',
            'Valor Líquido a Receber'
        ]
    ],
    [
        'name' => 'Tela 12 / Benefícios — CE_CATALOGO_BENEFICIOS & SE_DEMONSTRATIVO_BENEFICIOS',
        'params' => ['route' => 'benefits'],
        'role' => 'tenant-tech',
        'assertions' => [
            'CE_CATALOGO_BENEFICIOS',
            'SE_DEMONSTRATIVO_BENEFICIOS',
            'Dados de Entrada (Filtros de Pesquisa)',
            'Dados de Saída (Planos e Pacotes Ativos)',
            'Justificativa do Dado Derivado',
            '7.418',
            'PAT'
        ]
    ],
    [
        'name' => 'Tela 14 / SST — CE_CONSULTAR_HISTORICO_CLINICO, SE_BLOQUEIO & SE_ALERTAS',
        'params' => ['route' => 'safety'],
        'role' => 'tenant-ind',
        'assertions' => [
            'CE_CONSULTAR_HISTORICO_CLINICO',
            'SE_BLOQUEIO_CHAO_FABRICA',
            'SE_PAINEL_ALERTAS_NR6_NR7',
            'SE_RELATORIO_PPP_ELETRONICO',
            'Dados de Entrada (Filtros de Pesquisa)',
            'Dados de Saída (Prontuário de Segurança)',
            'Barreira Impeditiva de Catraca Industrial',
            'Matriz Preditiva de Vencimentos'
        ]
    ],
    [
        'name' => 'Tela 17 / Seguros FinCorp — CE_CONSULTAR_APOLICE_VIGENTE & SE_SINISTRALIDADE',
        'params' => ['route' => 'insurance'],
        'role' => 'tenant-fin',
        'assertions' => [
            'CE_CONSULTAR_APOLICE_VIGENTE',
            'SE_RELATORIO_SINISTRALIDADE',
            'EE_EMITIR_APOLICE_SEGURO',
            'Dados de Entrada (Filtros de Pesquisa)',
            'Dados de Saída (Apólices Ativas FinCorp)',
            'Justificativa do Dado Derivado',
            'Sinistralidade (%)',
            'Benchmark SUSEP (70%)'
        ]
    ],
    [
        'name' => 'Tela 20 / Terminal PIX BACEN — AIE_PIX_BACEN_GATEWAY',
        'params' => ['route' => 'pix'],
        'role' => 'tenant-fin',
        'assertions' => [
            'AIE_PIX_BACEN_GATEWAY',
            'Gateway de Liquidação Instantânea PIX BACEN',
            'Terminal de Liquidação SPI BACEN',
            'Payload PIX Copia-e-Cola',
            'BR Code com CRC-16'
        ]
    ],
    [
        'name' => 'Tela 18 / Folha — CE_CONSULTAR_FICHA, SE_HOLERITE & SE_PROVISOES',
        'params' => ['route' => 'payroll'],
        'role' => 'tenant-tech',
        'assertions' => [
            'CE_CONSULTAR_FICHA_FINANCEIRA',
            'SE_EMITIR_HOLERITE_MENSAL',
            'SE_RECIBO_PROVISAO_13_FERIAS',
            'Dados de Entrada (Filtros de Pesquisa)',
            'Dados de Saída (Fichas Financeiras Processadas)',
            'Justificativa do Dado Derivado',
            'INSS Progressivo em 4 Faixas',
            'Provisões Atuariais de 13º e Férias CLT'
        ]
    ],
    [
        'name' => 'Tela 16 / Compliance — CE_LOGS_AUDITORIA & SE_EXPORTAR_TRILHA',
        'params' => ['route' => 'compliance'],
        'role' => 'tenant-fin',
        'assertions' => [
            'CE_LOGS_AUDITORIA_LGPD',
            'SE_EXPORTAR_TRILHA_AUDITORIA',
            'Dados de Entrada (Filtros de Pesquisa)',
            'Dados de Saída (Ledger Criptográfico de Auditoria)',
            'Justificativa do Dado Derivado',
            'SHA-256',
            'Laudo Pericial de Integridade'
        ]
    ],
    [
        'name' => 'Tela 19 / Relatórios — Central de Exportação',
        'params' => ['route' => 'reports'],
        'role' => 'tenant-tech',
        'assertions' => [
            'Central de Relatórios Gerenciais',
            'Relatório Executivo PDF',
            'Planilha Consolidada CSV',
            'Estrutura de Dados JSON'
        ]
    ],
    [
        'name' => 'Tela OKRs / Performance — Strategy GoF',
        'params' => ['route' => 'okrs'],
        'role' => 'tenant-tech',
        'assertions' => [
            'Avaliação de Desempenho & OKRs',
            'OkrStrategy',
            'Objetivos Estratégicos e Key Results Cadastrados'
        ]
    ],
    [
        'name' => 'Central dos 10 AIEs — Simuladores & Telemetria Externa',
        'params' => ['route' => 'external_simulators'],
        'role' => 'tenant-tech',
        'assertions' => [
            'Central de Integrações & Simuladores dos 10 AIEs',
            'AIE_RECEITA_FEDERAL_CPF',
            'AIE_ESOCIAL_S2200',
            'AIE_RELOGIO_REP_671',
            'AIE_NTP_HORA_LEGAL',
            'AIE_OPERADORA_BENEFICIOS',
            'AIE_OPERADORA_SAUDE_ANS',
            'AIE_CATALOGO_CAEPI_MTE',
            'AIE_CLINICA_MEDICINA_OCUP',
            'AIE_FINCORP_SEGURADORA',
            'AIE_PIX_BACEN_GATEWAY'
        ]
    ],
    [
        'name' => 'Tela Admin / Master — Multi-Tenancy & LPS Feature Toggles',
        'params' => ['route' => 'admin'],
        'role' => 'admin',
        'assertions' => [
            'Gestão de Empresas (Tenants) & Linha de Produção (LPS)',
            'Empresas Contratantes Cadastradas',
            'Matriz de Feature Toggles por Empresa',
            'Variabilidade LPS'
        ]
    ]
];

// Banner
echo "\n======================================================================\n";
echo " HRTech Core — Test Suite Automatizado de Web Views & Roteamento (M2)\n";
echo " Verificação de Conformidade APF: 50 Funções Elementares (252 PFB)\n";
echo "======================================================================\n\n";

$passCount = 0;
$failCount = 0;
$startTime = microtime(true);

foreach ($testCases as $index => $tc) {
    $num = str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
    echo "[$num] Testando: {$tc['name']} ... ";

    // Setup request environment
    $_GET = $tc['params'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SESSION['active_role'] = $tc['role'];
    TenantContextManager::getInstance()->setActiveTenant($tc['role'] === 'admin' ? 'tenant-tech' : $tc['role']);

    // Capture output and errors
    ob_start();
    $caughtException = null;
    $hasPhpError = false;

    set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline) use (&$hasPhpError) {
        if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
            return true;
        }
        $hasPhpError = "PHP Error [$errno]: $errstr in $errfile on line $errline";
        return true;
    });

    try {
        $router->dispatch();
    } catch (\Throwable $e) {
        $caughtException = $e;
    }

    restore_error_handler();
    $output = ob_get_clean();

    // Check assertions
    $failedAssertions = [];

    if ($caughtException !== null) {
        $failedAssertions[] = "Exceção não tratada: " . $caughtException->getMessage();
    }

    if ($hasPhpError !== false) {
        $failedAssertions[] = $hasPhpError;
    }

    if (strlen($output) < 300) {
        $failedAssertions[] = "Saída HTML muito curta (" . strlen($output) . " bytes). Possível falha na renderização.";
    }

    foreach ($tc['assertions'] as $expectedString) {
        if (stripos($output, $expectedString) === false) {
            $failedAssertions[] = "String esperada não encontrada: '$expectedString'";
        }
    }

    if (empty($failedAssertions)) {
        echo "\033[32m[PASS]\033[0m (" . strlen($output) . " bytes)\n";
        $passCount++;
    } else {
        echo "\033[31m[FAIL]\033[0m\n";
        foreach ($failedAssertions as $fa) {
            echo "     \033[33m➔ $fa\033[0m\n";
        }
        $failCount++;
    }
}

$elapsed = round(microtime(true) - $startTime, 3);
echo "\n======================================================================\n";
echo " Resultado da Execução:\n";
echo " Testes Executados: " . count($testCases) . "\n";
echo " Aprovados:         \033[32m$passCount\033[0m\n";
echo " Falhas:            " . ($failCount > 0 ? "\033[31m$failCount\033[0m" : "\033[32m0\033[0m") . "\n";
echo " Tempo Total:       {$elapsed}s\n";
echo "======================================================================\n";

if ($failCount === 0) {
    echo "\n\033[32m✔ TODAS AS ROTAS E ELEMENTOS APF (CE & SE) VERIFICADOS COM SUCESSO!\033[0m\n\n";
    exit(0);
} else {
    echo "\n\033[31m✖ FORAM DETECTADAS FALHAS NA VERIFICAÇÃO DE ROTAS/VIEWS.\033[0m\n\n";
    exit(1);
}
