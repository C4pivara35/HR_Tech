<?php
declare(strict_types=1);

namespace App\Controllers;

use PDO;
use App\Services\External\AieReceitaCpfService;
use App\Services\External\AieGovEsocialService;
use App\Services\External\AieRelogioRepService;
use App\Services\External\AieNtpService;
use App\Services\External\AieOperadoraBeneficiosService;
use App\Services\External\AieOperadoraSaudeService;
use App\Services\External\AieMteCaService;
use App\Services\External\AieClinicaMedicaService;
use App\Services\External\AieSeguradoraService;
use App\Services\External\AiePixBacenService;

/**
 * ==============================================================================
 * ExternalSimulatorsController — Central Interativa dos 10 AIEs (Serviços Externos)
 * ==============================================================================
 * Cobre 100% dos 10 Arquivos de Interface Externa (AIEs) do catálogo IFPUG / NESMA:
 *
 * 1. Andryus: AIE_RECEITA_FEDERAL_CPF (Receita Federal / Serpro)
 * 2. Andryus: AIE_ESOCIAL_S2200 (Portal eSocial Gov.br)
 * 3. Felipe:   AIE_RELOGIO_REP_671 (Relógio Eletrônico de Ponto AFD)
 * 4. Felipe:   AIE_NTP_HORA_LEGAL (NTP.br Observatório Nacional)
 * 5. Valentin: AIE_OPERADORA_BENEFICIOS (Caju / Ticket / Flash APIs)
 * 6. Valentin: AIE_OPERADORA_SAUDE_ANS (Bradesco Saúde / ANS TISS)
 * 7. Nicolas:  AIE_CATALOGO_CAEPI_MTE (Base Pública CAEPI MTE)
 * 8. Nicolas:  AIE_CLINICA_MEDICINA_OCUP (SOC Telemedicina Ocupacional)
 * 9. Fernando: AIE_FINCORP_SEGURADORA (FinCorp Underwriting SUSEP)
 * 10. Fernando: AIE_PIX_BACEN_GATEWAY (Gateway PIX SPI BACEN)
 * ==============================================================================
 */
class ExternalSimulatorsController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): void {
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        $filterService = trim($_GET['service_name'] ?? '');

        $sql = "SELECT * FROM external_integrations WHERE 1=1";
        $params = [];
        if (!empty($filterService)) {
            $sql .= " AND service_name = ?";
            $params[] = $filterService;
        }
        $sql .= " ORDER BY created_at DESC LIMIT 40";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $integrationLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agregação estatística de chamadas
        $statsStmt = $this->pdo->query(
            "SELECT service_name, 
                    COUNT(*) as total_calls, 
                    AVG(duration_ms) as avg_latency, 
                    MAX(created_at) as last_call,
                    SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) as successful_calls
             FROM external_integrations 
             GROUP BY service_name"
        );
        $serviceStats = [];
        while ($row = $statsStmt->fetch(PDO::FETCH_ASSOC)) {
            $serviceStats[$row['service_name']] = $row;
        }

        // Catálogo das 10 APIs externas
        $catalog = [
            'RECEITA_FEDERAL_CPF' => [
                'name' => 'API Receita Federal / SERPRO (Validação de CPF)',
                'ifpug_code' => 'AIE_RECEITA_FEDERAL_CPF',
                'member' => 'Andryus Zolet',
                'complexity' => 'Baixa (5 PFB)',
                'endpoint' => 'https://api.serpro.gov.br/consulta-cpf/v1/validate',
                'description' => 'Consulta situação cadastral do CPF na base oficial da Receita Federal com Módulo 11.'
            ],
            'ESOCIAL_S2200' => [
                'name' => 'Portal eSocial Nacional Gov.br (Evento S-2200)',
                'ifpug_code' => 'AIE_ESOCIAL_S2200',
                'member' => 'Andryus Zolet',
                'complexity' => 'Média (7 PFB)',
                'endpoint' => 'https://ws.esocial.gov.br/servico/enviarlote/v1_2_0',
                'description' => 'Transmissão canônica de XML S-2200 com assinatura digital e digestão SHA-256.'
            ],
            'RELOGIO_REP_671' => [
                'name' => 'Relógio REP-C / REP-P (Portaria 671 MTE)',
                'ifpug_code' => 'AIE_RELOGIO_REP_671',
                'member' => 'Felipe',
                'complexity' => 'Baixa (5 PFB)',
                'endpoint' => 'tcp://rep-device.local:8080/afd/export',
                'description' => 'Importação de arquivo fiscal AFD (Arquivo Fonte de Dados) de relógio físico homologado.'
            ],
            'NTP_HORA_LEGAL' => [
                'name' => 'NTP.br (Hora Legal Brasileira - Observatório Nacional)',
                'ifpug_code' => 'AIE_NTP_HORA_LEGAL',
                'member' => 'Felipe',
                'complexity' => 'Baixa (5 PFB)',
                'endpoint' => 'udp://a.st1.ntp.br:123',
                'description' => 'Sincronização com relógio atômico Stratum 1 e carimbo de tempo inviolável.'
            ],
            'OPERADORA_BENEFICIOS' => [
                'name' => 'Gateway de Operadoras de Benefícios (Caju / Ticket / Flash)',
                'ifpug_code' => 'AIE_OPERADORA_BENEFICIOS',
                'member' => 'Valentin',
                'complexity' => 'Média (7 PFB)',
                'endpoint' => 'https://api.caju.com.br/v2/cards/recharge',
                'description' => 'Recarga automática e conciliação de cartões multibenefícios flexíveis.'
            ],
            'OPERADORA_SAUDE_ANS' => [
                'name' => 'Operadora de Saúde Suplementar (ANS TISS 4.01.00)',
                'ifpug_code' => 'AIE_OPERADORA_SAUDE_ANS',
                'member' => 'Valentin',
                'complexity' => 'Baixa (5 PFB)',
                'endpoint' => 'https://tiss.bradescosaude.com.br/v4_01_00/autorizacao',
                'description' => 'Elegibilidade de consultas e procedimentos ambulatoriais com coparticipação.'
            ],
            'CATALOGO_CAEPI_MTE' => [
                'name' => 'Base Pública CAEPI (Ministério do Trabalho e Emprego)',
                'ifpug_code' => 'AIE_CATALOGO_CAEPI_MTE',
                'member' => 'Nicolas',
                'complexity' => 'Baixa (5 PFB)',
                'endpoint' => 'https://consultaca.com.br/api/ca/v1',
                'description' => 'Consulta de validade de Certificados de Aprovação (CA) para entrega de EPIs (NR-06).'
            ],
            'CLINICA_MEDICINA_OCUP' => [
                'name' => 'SOC Telemedicina & Clínica Ocupacional Parceira',
                'ifpug_code' => 'AIE_CLINICA_MEDICINA_OCUP',
                'member' => 'Nicolas',
                'complexity' => 'Baixa (5 PFB)',
                'endpoint' => 'https://soc.com.br/ws/aso/v3/schedule',
                'description' => 'Agendamento e emissão de ASOs com assinatura médica qualificada CRM (NR-07).'
            ],
            'FINCORP_SEGURADORA' => [
                'name' => 'FinCorp Enterprise Underwriting Gateway (SUSEP)',
                'ifpug_code' => 'AIE_FINCORP_SEGURADORA',
                'member' => 'Fernando Lopes Duarte',
                'complexity' => 'Média (7 PFB)',
                'endpoint' => 'https://underwriting.fincorpseguros.com.br/v1/proposals',
                'description' => 'Subscrição de riscos e tarifação atuarial de apólices coletivas de vida e D&O.'
            ],
            'PIX_BACEN_GATEWAY' => [
                'name' => 'Gateway PIX SPI (Banco Central do Brasil)',
                'ifpug_code' => 'AIE_PIX_BACEN_GATEWAY',
                'member' => 'Fernando Lopes Duarte',
                'complexity' => 'Baixa (5 PFB)',
                'endpoint' => 'https://spi.bcb.gov.br/api/v2/pix',
                'description' => 'Geração de QR Code dinâmico EMVCo e liquidação instantânea via protocolo BACEN.'
            ]
        ];

        if (function_exists('logQuery')) {
            logQuery("SELECT FROM external_integrations [Catálogo 10 AIEs]", [$activeTenantId], "Catálogo 10 AIEs");
        }

        require ROOT_DIR . '/app/Views/layout/header.php';
        require ROOT_DIR . '/app/Views/external_simulators/index.php';
        require ROOT_DIR . '/app/Views/layout/footer.php';
    }

    public function testService(): void {
        $service = $_GET['service'] ?? '';
        $activeRole = $_SESSION['active_role'] ?? 'tenant-tech';
        $activeTenantId = ($activeRole === 'admin') ? 'tenant-tech' : $activeRole;

        try {
            switch ($service) {
                case 'RECEITA_FEDERAL_CPF':
                    $s = new AieReceitaCpfService($this->pdo);
                    $res = $s->validateCpf('52998224725', $activeTenantId);
                    $_SESSION['flash_message'] = "API Receita Federal testada com sucesso! Protocolo: {$res['protocol_number']}";
                    break;

                case 'ESOCIAL_S2200':
                    $s = new AieGovEsocialService($this->pdo);
                    $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? LIMIT 1");
                    $stmt->execute([$activeTenantId]);
                    $emp = $stmt->fetch();
                    $res = $s->transmitS2200Event($emp ?: ['full_name' => 'Ana Beatriz', 'cpf' => '529.982.247-25', 'employment_type' => 'CLT', 'matricula' => 'MAT-1001', 'base_salary_cents' => 850000], $activeTenantId);
                    $_SESSION['flash_message'] = "eSocial S-2200 transmitido com sucesso! Recibo: {$res['receipt_number']}";
                    break;

                case 'RELOGIO_REP_671':
                    $s = new AieRelogioRepService($this->pdo);
                    $res = $s->importAfdFile('REP-001', $activeTenantId);
                    $_SESSION['flash_message'] = "Relógio REP 671 sincronizado! {$res['records_imported']} marcações importadas no lote {$res['batch_id']}.";
                    break;

                case 'NTP_HORA_LEGAL':
                    $s = new AieNtpService($this->pdo);
                    $res = $s->getLegalTime('127.0.0.1', $activeTenantId);
                    $_SESSION['flash_message'] = "NTP.br sincronizado! Hora Legal: {$res['legal_time']} (Stratum: {$res['stratum']}, Drift: {$res['drift_ms']}ms).";
                    break;

                case 'OPERADORA_BENEFICIOS':
                    $s = new AieOperadoraBeneficiosService($this->pdo);
                    $res = $s->rechargeBenefitsCard('emp-ana', 'MEAL_VOUCHER', 65000, $activeTenantId);
                    $_SESSION['flash_message'] = "Caju Benefícios recarregado com sucesso! NSU: {$res['nsu_authorization']}";
                    break;

                case 'OPERADORA_SAUDE_ANS':
                    $s = new AieOperadoraSaudeService($this->pdo);
                    $res = $s->verifyEligibilityAndCopay('emp-ana', 'ANS-40100', 35000, $activeTenantId);
                    $_SESSION['flash_message'] = "ANS Saúde autorizada! Guia TISS: {$res['tiss_authorization_guide']}";
                    break;

                case 'CATALOGO_CAEPI_MTE':
                    $s = new AieMteCaService($this->pdo);
                    $res = $s->verifyCertificateOfApproval('31469', $activeTenantId);
                    $_SESSION['flash_message'] = "Base CAEPI MTE consultada! CA {$res['ca_number']} encontra-se {$res['status']}.";
                    break;

                case 'CLINICA_MEDICINA_OCUP':
                    $s = new AieClinicaMedicaService($this->pdo);
                    $res = $s->scheduleExamAndIssueAso('emp-joao', 'PERIODIC', 1, $activeTenantId);
                    $_SESSION['flash_message'] = "SOC Medicina agendado! ASO Protocolo: {$res['protocol_number']}";
                    break;

                case 'FINCORP_SEGURADORA':
                    $s = new AieSeguradoraService($this->pdo);
                    $stmt = $this->pdo->prepare("SELECT * FROM employees WHERE tenant_id = ? LIMIT 1");
                    $stmt->execute([$activeTenantId]);
                    $emp = $stmt->fetch();
                    $res = $s->underwriteLifePolicy($emp ?: ['full_name' => 'Helena Castro', 'birth_date' => '1988-03-20', 'employment_type' => 'CLT'], 60000000, 'LIFE_GROUP', $activeTenantId);
                    $_SESSION['flash_message'] = "Subscrição FinCorp concluída! Apólice Proposta: {$res['policy_number']}";
                    break;

                case 'PIX_BACEN_GATEWAY':
                    $s = new AiePixBacenService($this->pdo);
                    $res = $s->generateDynamicPix(null, 25000, 'Simulação de Pagamento BACEN', $activeTenantId);
                    $_SESSION['flash_message'] = "Gateway PIX SPI executado! Payload EMVCo gerado com TxID {$res['txid']}";
                    break;

                default:
                    $_SESSION['flash_error'] = "Serviço externo '$service' não reconhecido.";
                    break;
            }
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Erro na simulação do serviço: " . $e->getMessage();
        }

        header("Location: index.php?route=external_simulators&service_name=" . urlencode($service));
        exit;
    }
}
