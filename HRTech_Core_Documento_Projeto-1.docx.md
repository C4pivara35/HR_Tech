**PONTIFÍCIA UNIVERSIDADE CATÓLICA DO PARANÁ**  
**ENGENHARIA DE SOFTWARE**

**HRTech Core**  
**Sistema Modular de Gestão de Recursos Humanos**

Projeto de Design Thinking, Reuso de Software, Linha de Produção e Medição

Documento de definição e detalhamento do projeto  
Agosto de 2026

# **1\. Visão geral**

O HRTech Core é uma plataforma modular de Gestão de Recursos Humanos criada para atender empresas diferentes a partir de uma mesma base de software. O sistema possui um núcleo comum para cadastros e gestão de colaboradores e permite habilitar módulos e regras conforme as necessidades de cada empresa.

A proposta é especialmente adequada à Linha de Produção de Software (LPS), pois a aplicação não precisa ser desenvolvida do zero para cada cliente. O núcleo, os modelos, componentes, testes e documentos podem ser reutilizados, enquanto as diferenças entre clientes são tratadas como variabilidades.

# **2\. Atendimento aos requisitos da disciplina**

O material de Projeto Medição-Reuso solicita Design Thinking, storyboard de 10–15 interações, seis personas com mapa de empatia e público-alvo, protótipo de 10–15 telas, ao menos um CRUD e 15 requisitos funcionais e 10 não funcionais. fileciteturn0file0L5-L13

Para Reuso, o projeto deve ser pensado como uma Linha de Produção de Software, permitindo reutilização e variabilidade entre clientes, além da criação de seis ativos reutilizáveis. Os ativos podem ser código, diagramas, modelos, testes, documentos, especificações e componentes de front-end. fileciteturn0file0L18-L32

| Entrega | Proposta no HRTech Core |
| :---- | :---- |
| Design Thinking | 6 personas, mapas de empatia, público-alvo, storyboard e protótipo. |
| CRUD | CRUD de colaboradores, departamentos e cargos. |
| Requisitos | 15 requisitos funcionais \+ 10 não funcionais. |
| LPS | Núcleo comum \+ módulos e regras configuráveis. |
| Reuso | 6 ativos reutilizáveis documentados. |
| Medição | Métricas de produto, processo, reuso e indicadores. |

# **3\. Problema e oportunidade**

Empresas possuem necessidades semelhantes de RH, porém suas operações variam. Uma empresa de tecnologia pode priorizar trabalho remoto, benefícios flexíveis, metas e avaliação 360°. Uma indústria pode priorizar presença, turnos, escalas, EPIs, atestados e exames ocupacionais.

Essa diferença torna o domínio adequado a uma LPS: funcionalidades comuns ficam no produto-base e características específicas são implementadas como módulos ou regras variáveis.

# **4\. Objetivos**

## **4.1 Objetivo geral**

Projetar uma plataforma modular de RH que possa gerar diferentes configurações do produto para clientes distintos, utilizando princípios de Design Thinking, Reuso, LPS e Medição de Software.

## **4.2 Objetivos específicos**

* Centralizar o cadastro e a gestão de colaboradores.  
* Controlar acesso por papéis e permissões.  
* Permitir ativação e desativação de módulos por cliente.  
* Modelar regras específicas para tecnologia e indústria.  
* Criar seis ativos reutilizáveis.  
* Projetar uma experiência adequada a RH, gestores e colaboradores.  
* Definir requisitos funcionais e não funcionais reutilizáveis.  
* Definir métricas que apoiem decisões sobre produto e processo.

# **5\. Domínio do sistema**

| Elemento | Descrição |
| :---- | :---- |
| Empresa/Tenant | Organização que utiliza uma configuração do HRTech Core. |
| Colaborador | Pessoa vinculada à empresa, departamento e cargo. |
| Departamento | Unidade organizacional. |
| Cargo | Função ocupada pelo colaborador. |
| Gestor | Usuário que acompanha equipes e aprova solicitações. |
| Administrador | Usuário responsável pela configuração e administração. |
| Solicitação | Pedido de férias, folga, ajuste de ponto ou outro processo. |
| Módulo | Conjunto de funcionalidades habilitável por cliente. |

# **6\. Variabilidade da Linha de Produção de Software**

A LPS terá um núcleo comum e pontos de variabilidade. A configuração de cada cliente define quais módulos estarão disponíveis e quais regras serão aplicadas.

| Característica | Cliente A — Tecnologia | Cliente B — Indústria |
| :---- | :---- | :---- |
| Trabalho | Remoto/híbrido | Presencial |
| Jornada | Banco de horas flexível | Turnos e escalas, como 12x36 |
| Benefícios | Benefícios flexíveis | Benefícios definidos pela empresa |
| Desempenho | Avaliação 360° e OKRs | Avaliação por função/conformidade |
| Segurança | Opcional | EPIs, atestados e ASO |
| Foco | Cultura, flexibilidade e metas | Presença, jornada e segurança |

A variabilidade deve existir no comportamento do sistema, não apenas na aparência. Exemplos: regras de jornada, permissões, relatórios, módulos disponíveis e cálculos.

# **7\. Personas e público-alvo**

Público-alvo: empresas que desejam centralizar processos de RH e que possuem necessidades semelhantes, mas regras operacionais diferentes.

| Persona | Perfil | Principais necessidades |
| :---- | :---- | :---- |
| Diretor(a) de RH | Decisor | Indicadores, visão consolidada e redução de trabalho operacional. |
| Analista de Departamento Pessoal | Operacional | Cadastros, ponto, férias, documentos e histórico. |
| Gerente de Engenharia | Gestor | Equipe, aprovações, metas e desempenho. |
| Desenvolvedor(a) remoto(a) | Colaborador | Ponto, banco de horas, benefícios e solicitações. |
| Operador(a) de fábrica | Colaborador | Escala, ponto, EPIs e solicitações. |
| Recrutador(a) | RH | Cargos, departamentos, estrutura e consulta de colaboradores. |

## **7.1 Mapas de empatia**

## **Diretor(a) de RH**

Pensa em dados confiáveis e eficiência; vê indicadores; ouve gestores e diretoria; faz análises e decisões; dores: sistemas fragmentados; ganhos: visão consolidada.

## **Analista de DP**

Pensa em executar tarefas sem erros; vê muitos registros; ouve colaboradores; faz validações e correções; dores: retrabalho; ganhos: automação.

## **Gerente de Engenharia**

Pensa em cuidar da equipe sem perder produtividade; vê solicitações e metas; ouve equipe e RH; faz aprovações; dores: processos manuais; ganhos: rapidez.

## **Desenvolvedor(a) remoto(a)**

Pensa em acompanhar sua jornada; vê dashboard pessoal; ouve gestor/RH; faz registros e solicitações; dores: falta de transparência; ganhos: autonomia.

## **Operador(a) de fábrica**

Pensa em ter a jornada registrada corretamente; vê escala e ponto; ouve liderança; faz marcações e acompanha EPIs; dores: divergências; ganhos: clareza.

## **Recrutador(a)**

Pensa em encontrar dados rapidamente; vê cargos e departamentos; ouve RH e gestores; faz consultas; dores: informação espalhada; ganhos: centralização.

# **8\. Storyboard — solicitação de férias**

| Etapa | História | Interface | Ação |
| :---- | :---- | :---- | :---- |
| 1 | Colaborador entra no sistema | Login | Autenticação |
| 2 | Visualiza o dashboard | Resumo pessoal | Saldo de férias |
| 3 | Acessa Férias | Menu | Consulta |
| 4 | Visualiza períodos disponíveis | Lista/calendário | Dados |
| 5 | Escolhe as datas | Calendário | Seleção |
| 6 | Sistema valida as regras | Mensagem | Validação |
| 7 | Confirma solicitação | Formulário | Envio |
| 8 | Sistema registra pedido | Status pendente | Persistência |
| 9 | Gestor recebe aviso | Notificação | Nova solicitação |
| 10 | Gestor analisa | Detalhes | Consulta |
| 11 | Gestor aprova/recusa | Botões | Decisão |
| 12 | Sistema atualiza status | Histórico | Auditoria |
| 13 | Colaborador recebe retorno | Notificação | Resultado |
| 14 | RH acompanha | Dashboard | Indicador |

# **9\. Protótipo — 15 telas**

| Tela | Nome | Objetivo |
| :---- | :---- | :---- |
| 1 | Login | Autenticação |
| 2 | Dashboard do colaborador | Resumo pessoal |
| 3 | Dashboard do RH | Indicadores |
| 4 | Lista de colaboradores | Pesquisa e CRUD |
| 5 | Cadastro/edição | Dados do colaborador |
| 6 | Detalhes do colaborador | Histórico |
| 7 | Organograma | Hierarquia |
| 8 | Espelho de ponto | Jornada e banco |
| 9 | Ajuste de ponto | Justificativa |
| 10 | Férias | Saldo e calendário |
| 11 | Aprovação do gestor | Solicitações |
| 12 | Benefícios | Adesões |
| 13 | Desempenho/OKRs | Metas e avaliações |
| 14 | EPIs e exames | Módulo industrial |
| 15 | Configuração da empresa | Módulos e permissões |

# **10\. Requisitos funcionais**

| ID | Requisito | Descrição |
| :---- | :---- | :---- |
| RF01 | Autenticar usuários | Permitir login e logout. |
| RF02 | Controlar acesso | Aplicar permissões conforme o papel. |
| RF03 | Cadastrar colaborador | Inserir novos colaboradores. |
| RF04 | Consultar colaborador | Pesquisar e visualizar colaboradores. |
| RF05 | Alterar colaborador | Editar dados. |
| RF06 | Inativar colaborador | Inativar registros conforme regra. |
| RF07 | Gerenciar departamentos | CRUD de departamentos. |
| RF08 | Gerenciar cargos | CRUD de cargos. |
| RF09 | Exibir organograma | Apresentar hierarquia. |
| RF10 | Registrar ponto | Registrar jornada. |
| RF11 | Calcular banco de horas | Calcular saldo conforme configuração. |
| RF12 | Solicitar férias/folgas | Criar solicitações. |
| RF13 | Aprovar solicitações | Gestor aprova ou recusa. |
| RF14 | Gerenciar benefícios | Cadastrar e disponibilizar benefícios. |
| RF15 | Gerar indicadores | Apresentar dashboards e relatórios. |

## **10.1 Requisitos funcionais variáveis**

* RFV01 — Tecnologia: avaliação 360°.  
* RFV02 — Tecnologia: acompanhamento de OKRs.  
* RFV03 — Tecnologia: regras de trabalho remoto/híbrido.  
* RFV04 — Indústria: escalas e turnos, incluindo 12x36.  
* RFV05 — Indústria: gestão de EPIs.  
* RFV06 — Indústria: atestados e exames/ASO.

# **11\. Requisitos não funcionais**

| ID | Categoria | Descrição |
| :---- | :---- | :---- |
| RNF01 | Segurança | Dados e senhas devem ser protegidos. |
| RNF02 | Desempenho | Consultas frequentes devem responder dentro do limite definido para o projeto. |
| RNF03 | Disponibilidade | O sistema deve permanecer disponível no ambiente de uso. |
| RNF04 | Usabilidade | Navegação e linguagem devem ser consistentes. |
| RNF05 | Responsividade | Interface adaptável a desktop e dispositivos móveis. |
| RNF06 | Manutenibilidade | Código modular e organizado. |
| RNF07 | Reusabilidade | Ativos devem ser reaproveitáveis. |
| RNF08 | Auditabilidade | Operações relevantes devem manter histórico. |
| RNF09 | Escalabilidade | Arquitetura deve suportar crescimento. |
| RNF10 | Testabilidade | Funcionalidades críticas devem possuir testes automatizados. |

# **12\. Seis ativos reutilizáveis**

| ID | Ativo | Tipo | Conteúdo |
| :---- | :---- | :---- | :---- |
| AR01 | Autenticação \+ RBAC | Código | Login, usuários, papéis e permissões. |
| AR02 | Organograma interativo | Front-end | Componente visual reutilizável. |
| AR03 | Motor de ponto/banco | Código | Regras de jornada e cálculo. |
| AR04 | Modelo de dados base | SQL/modelo | Entidades e relacionamentos centrais. |
| AR05 | Suíte de testes | Testes | CRUD e fluxos críticos. |
| AR06 | Matriz de requisitos | Documento | Requisitos e pontos de variabilidade. |

Essa definição segue a orientação do material de Reuso, que apresenta como possíveis ativos diagramas, código-fonte, modelos, casos de teste, documentos, especificações e front-end. fileciteturn0file0L25-L29

# **13\. Medição de Software**

O material de Introdução à Medição apresenta a medição como avaliação quantitativa que ajuda no planejamento, controle e melhoria do software e de seu processo. fileciteturn0file1L215-L222 Também apresenta indicador como uma métrica ou combinação de métricas que fornece informações sobre processo, projeto ou produto. fileciteturn0file1L269-L283

| Tipo | Métrica/Indicador | Unidade | Objetivo |
| :---- | :---- | :---- | :---- |
| Produto | Tempo médio da API de ponto | ms | Avaliar desempenho. |
| Produto | Cobertura de testes do CRUD | % | Avaliar testabilidade. |
| Processo | Tempo para implementar funcionalidade | horas/dias | Acompanhar esforço. |
| Processo | Defeitos encontrados | quantidade | Acompanhar qualidade. |
| Reuso | Percentual de ativos reutilizados | % | Avaliar efetividade da LPS. |
| Reuso | Tempo para instanciar novo cliente | horas/dias | Medir ganho de reuso. |
| RH | Taxa de absenteísmo | % | Acompanhar ausências. |
| RH | Horas extras acumuladas | horas | Acompanhar jornada. |
| Processo | Tempo médio de aprovação | horas/dias | Avaliar eficiência. |
| Produto | Solicitações processadas sem erro | % | Avaliar confiabilidade. |

Exemplo: para o tempo médio da API de ponto, registrar o tempo entre requisição e resposta, calcular a média e acompanhar a tendência ao longo das versões. O resultado pode indicar necessidade de otimização.

A medição também evita que avaliações dependam apenas de julgamento subjetivo: o material destaca que medições permitem detectar tendências, melhorar estimativas e obter aperfeiçoamentos reais ao longo do tempo. fileciteturn0file1L245-L252

# **14\. Regras de negócio iniciais**

1. RB01 — Todo colaborador pertence a uma empresa/tenant.  
2. RB02 — Colaborador deve possuir departamento e cargo conforme configuração.  
3. RB03 — Alterações sensíveis devem exigir permissão adequada.  
4. RB04 — Férias devem respeitar regras configuradas para o cliente.  
5. RB05 — Ajustes de ponto devem registrar justificativa.  
6. RB06 — Aprovações devem registrar responsável e data.  
7. RB07 — Banco de horas usa parâmetros da empresa.  
8. RB08 — Módulos somente ficam disponíveis quando habilitados.  
9. RB09 — Usuários somente visualizam dados permitidos.  
10. RB10 — Parâmetros devem ser alteráveis sem modificar o núcleo sempre que possível.

# **15\. Casos de uso prioritários do MVP**

| ID | Caso de uso | Ator | Objetivo |
| :---- | :---- | :---- | :---- |
| UC01 | Autenticar usuário | Todos | Entrar no sistema. |
| UC02 | Gerenciar colaboradores | RH/Admin | Executar CRUD. |
| UC03 | Consultar organograma | Usuários autorizados | Visualizar hierarquia. |
| UC04 | Registrar ponto | Colaborador | Registrar jornada. |
| UC05 | Ajustar ponto | Colaborador | Enviar justificativa. |
| UC06 | Solicitar férias | Colaborador | Enviar pedido. |
| UC07 | Aprovar solicitação | Gestor | Aprovar/recusar. |
| UC08 | Consultar banco de horas | Colaborador/Gestor | Visualizar saldo. |
| UC09 | Configurar módulos | Admin | Ativar/desativar módulos. |
| UC10 | Visualizar indicadores | RH/Gestor/Admin | Consultar dashboards. |

# **16\. Arquitetura técnica inicial**

* Front-end web responsivo com componentes reutilizáveis.  
* Back-end organizado por módulos/domínios e disponibilizado por API.  
* Banco de dados relacional compartilhando o núcleo comum.  
* Autenticação com papéis e permissões.  
* Testes unitários e de integração para funcionalidades críticas.  
* Camada de configuração para ativação de módulos e regras por cliente.  
* Versionamento dos ativos reutilizáveis e documentação de suas interfaces.

# **17\. Plano de desenvolvimento sugerido**

| Etapa | Fase | Entrega |
| :---- | :---- | :---- |
| 1 | Descoberta | Problema, domínio e público. |
| 2 | Design Thinking | Personas, empatia e storyboard. |
| 3 | Requisitos | 15 RF, 10 RNF, regras e casos de uso. |
| 4 | Protótipo | 10–15 telas. |
| 5 | LPS | Núcleo, variabilidades e ativos. |
| 6 | MVP | Login, CRUD e solicitações. |
| 7 | Reuso | Implementar e testar ativos. |
| 8 | Medição | Definir e coletar métricas. |
| 9 | Validação | Testes e análise. |
| 10 | Apresentação | Demonstrar duas configurações de cliente. |

# **18\. Conclusão**

O HRTech Core apresenta um domínio adequado ao projeto porque combina funcionalidades comuns de RH com diferenças claras entre tipos de clientes. A mesma plataforma pode atender uma empresa de tecnologia e uma indústria sem que seja necessário reconstruir todo o software.

O principal diferencial acadêmico será demonstrar que a variabilidade está incorporada à solução: módulos, regras, permissões e cálculos podem mudar conforme a configuração do cliente, enquanto os ativos centrais permanecem reutilizáveis.

Como primeiro incremento, recomenda-se implementar autenticação, CRUD de colaboradores e o fluxo de solicitação/aprovação de férias ou ajuste de ponto. Depois, as funcionalidades específicas de Tecnologia e Indústria podem ser ativadas para demonstrar a LPS.

# **19\. Referências**

Pontifícia Universidade Católica do Paraná. Grupo de Pesquisa em Engenharia de Software. Projeto Medição-Reuso. Material fornecido para a disciplina.

Pontifícia Universidade Católica do Paraná. Grupo de Pesquisa em Engenharia de Software. Introdução à Medição. Material fornecido para a disciplina.