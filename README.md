# 🏢 HRTech Core — Plataforma Modular de Gestão de RH

> **Projeto de Medição e Análise de Software — Faculdade**  
> Abordagem de **Linha de Produção de Software (LPS)** para gestão de recursos humanos com variabilidade configurável entre empresas de Tecnologia e Indústria.

---

## 📋 Sobre o Projeto

O **HRTech Core** é uma plataforma modular de RH construída com a estratégia de **Linha de Produção de Software (LPS)**, que permite atender dois perfis de clientes completamente distintos a partir de um núcleo comum reutilizável.

### 🎯 Públicos-Alvo

| | Cliente A — Tecnologia | Cliente B — Indústria |
|---|---|---|
| **Modelo de Trabalho** | Remoto / Híbrido | 100% Presencial (Turnos / 12x36) |
| **Jornada** | Banco de horas flexível | Escalas rígidas e controle de turno |
| **Benefícios** | Cartão multibenefícios (Caju/Flash) | Benefícios fixos por convenção coletiva |
| **Desempenho** | Avaliação 360° + OKRs | Avaliação por função e conformidade legal |
| **Segurança** | Módulo de EPIs desabilitado | Gestão de EPIs, ASO e Exames obrigatórios |

---

## 🧩 Artefatos de Design Thinking

Este repositório documenta todos os artefatos produzidos durante o processo de design centrado no usuário:

### 👥 6 Personas Mapeadas

| Persona | Perfil | Dores Principais | Ganhos no HRTech Core |
|---|---|---|---|
| **Mariana Santos** | Diretora de RH (Decisor) | Sistemas fragmentados | Visão consolidada em tempo real |
| **Roberto Lima** | Analista de DP (Operacional) | Retrabalho e erros manuais | Automação de folha e ponto |
| **Carlos Eduardo** | Gerente de Engenharia (Gestor) | Aprovações lentas e burocráticas | Gestão ágil da equipe |
| **Lucas Silva** | Dev Remoto (Colaborador Tech) | Falta de transparência no banco de horas | Autonomia e visibilidade de saldo |
| **João Oliveira** | Operador de Fábrica (Colaborador Ind.) | Divergências no registro de turno | Registro seguro e espelho de ponto |
| **Ana Souza** | Recrutadora (RH) | Informação espalhada em múltiplos sistemas | Organograma centralizado |

### 🎬 Storyboard (14 Interações)

O storyboard documenta o fluxo completo de **Solicitação de Férias**, mapeando tanto a **história do usuário** quanto a **interface do produto** em 14 etapas sequenciais:

1. Autenticação no sistema
2. Visualização do Dashboard pessoal
3. Acesso ao módulo de férias
4. Consulta de períodos aquisitivos
5. Seleção das datas
6. Validação das regras do cliente
7. Envio da solicitação
8. Registro do pedido como pendente
9. Notificação automática ao gestor
10. Análise de conflitos de escala pelo gestor
11. Decisão (Aprovação / Recusa)
12. Auditoria e histórico da alteração
13. Retorno ao colaborador com resultado
14. Atualização dos indicadores no Dashboard de RH

---

## 💻 Protótipo Interativo — 15 Telas

Aplicação web completa e interativa, com tema escuro moderno (dark mode), componentes reutilizáveis e **switcher de tenant** (Tech vs. Indústria) para demonstrar a variabilidade LPS em tempo real.

| Tela | Descrição | Ativo LPS |
|:---:|---|:---:|
| **01** | Login & Autenticação | — |
| **02** | Dashboard do Colaborador | — |
| **03** | Dashboard Executivo do RH & Indicadores | — |
| **04** | Lista de Colaboradores (CRUD completo) | AR01 |
| **05** | Form de Cadastro / Edição (campos adaptativos) | AR01 |
| **06** | Perfil Detalhado do Colaborador | AR01 |
| **07** | Organograma Interativo | AR02 |
| **08** | Espelho de Ponto & Banco de Horas | AR03 |
| **09** | Solicitação de Ajuste de Ponto | AR03 |
| **10** | Gestão de Férias & Calendário | AR04 |
| **11** | Central de Aprovações do Gestor | AR04 |
| **12** | Gestão de Benefícios (Flexíveis vs. Fixos) | AR05 |
| **13** | Desempenho, OKRs & Avaliação 360° | RFV01/02 |
| **14** | Módulo Industrial: EPIs & Exames ASO | RFV05/06 |
| **15** | Configuração da Empresa & Variabilidades LPS | UC09 |

---

## 🚀 Como Executar

### Opção 1 — Direto no navegador

```bash
# Abra o arquivo diretamente
xdg-open index.html   # Linux
open index.html        # macOS
```

### Opção 2 — Servidor local

```bash
# Python (já incluso no sistema)
python3 -m http.server 8085

# Acesse no navegador:
# http://localhost:8085
```

---

## 🗂️ Estrutura do Repositório

```
HR_Tech/
├── index.html              # Aplicação principal (15 telas)
├── styles.css              # Design system completo (dark theme)
├── app.js                  # Lógica interativa, CRUD e tenant switcher
├── assets/
│   ├── storyboard_*.jpg    # Storyboard visual (14 etapas)
│   ├── persona_diretor_rh_*.jpg
│   ├── persona_analista_dp_*.jpg
│   ├── persona_gerente_engenharia_*.jpg
│   ├── persona_desenvolvedor_remoto_*.jpg
│   ├── persona_operador_fabrica_*.jpg
│   ├── persona_recrutador_*.jpg
│   └── publico_alvo_*.jpg  # Infográfico de Público-Alvo
└── HRTech_Core_Documento_Projeto-1.docx.pdf   # Documento base do projeto
```

---

## 🛠️ Tecnologias Utilizadas

- **HTML5** — Estrutura semântica e acessível
- **CSS3 Vanilla** — Design system com variáveis CSS, dark mode e animações
- **JavaScript ES6+** — Lógica de SPA, CRUD dinâmico e gerenciamento de estado
- **Font Awesome 6** — Biblioteca de ícones
- **Google Fonts** — Tipografia (Plus Jakarta Sans + JetBrains Mono)

---

## 📐 Métricas de Medição (LPS)

| Métrica | Valor Medido | Meta |
|---|---|---|
| Tempo médio API Ponto | 120 ms | < 200 ms |
| Cobertura de testes CRUD | 92% | > 85% |
| Ativos reutilizados (%) | 83,3% (5/6) | > 75% |
| Tempo para instanciar novo cliente | 4 horas | < 8 horas |

---

## 👨‍💻 Autores

**Fernando Lopes Duarte** — [@Fernando-Lopes1](https://github.com/Fernando-Lopes1)

> Projeto desenvolvido para a disciplina de **Medição e Análise de Software**  
> Faculdade — 2026/1

---

## 📄 Licença

Este projeto é de uso acadêmico. Todos os direitos reservados aos autores.
