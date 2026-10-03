# 📋 Roteiro de Gravação: Compactação de Código Focada em Reuso

> **Trabalho de Faculdade — Medição e Análise de Software (Item 03)**  
> **Objetivo:** Gravar a demonstração/passo a passo das técnicas de compactação de código focadas em reuso aplicadas no repositório.

---

## 🎥 1. Ferramentas de Gravação (Escolha uma)

- **Gravador de Tela do Windows (Recomendado):**  
  Pressione **`Windows + Alt + R`** (ou `Windows + G` para abrir a Game Bar) para iniciar a gravação MP4.
- **Gravador de Passos do Windows (PSR):**  
  Pressione `Windows + R`, digite `psr`, dê **Enter** e clique em **Iniciar Captura**.

---

## 🎬 2. Roteiro de Gravação Passo a Passo

### 🎬 1. Apresentação Inicial (30 segundos)
- Abra o repositório no GitHub ou no VS Code.
- Fala sugerida:
  > *"Olá! Somos a equipe do projeto de Medição e Análise de Software. Hoje vamos demonstrar o Item 03 do trabalho: a aplicação de técnicas de compactação de código focadas em reuso, seguindo o princípio DRY (Don't Repeat Yourself)."*

---

### 🎬 2. Método 1: PHP Traits para Entidades de Domínio
- Abra o arquivo: [`hrtech_backend_patterns/src/Domain/Traits/EntityBaseTrait.php`](file:///home/fernando/Documentos/Faculdade/Projeto%20de%20medição%20e%20analise/hrtech_backend_patterns/src/Domain/Traits/EntityBaseTrait.php)
- Abra também a entidade: [`hrtech_backend_patterns/src/Domain/Entities/Employee.php`](file:///home/fernando/Documentos/Faculdade/Projeto%20de%20medição%20e%20analise/hrtech_backend_patterns/src/Domain/Entities/Employee.php)
- Fala sugerida:
  > *"A primeira técnica utilizada foi o uso de **PHP Traits**. No arquivo `EntityBaseTrait.php`, centralizamos atributos comuns como `$id`, `$tenantId`, `$createdAt` e seus respectivos getters. Assim, classes como `Employee` e `Tenant` usam apenas uma linha (`use EntityBaseTrait;`), eliminando mais de 25 linhas de código duplicado por entidade."*

---

### 🎬 3. Método 2: Padrões de Projeto (Strategy & Template Method)
- Mostre a pasta: [`hrtech_backend_patterns/src/Patterns/Strategy/`](file:///home/fernando/Documentos/Faculdade/Projeto%20de%20medição%20e%20analise/hrtech_backend_patterns/src/Patterns/Strategy)
- Mostre a pasta: [`hrtech_backend_patterns/src/Patterns/TemplateMethod/`](file:///home/fernando/Documentos/Faculdade/Projeto%20de%20medição%20e%20analise/hrtech_backend_patterns/src/Patterns/TemplateMethod)
- Fala sugerida:
  > *"Também aplicamos os padrões **Strategy** e **Template Method**. O padrão Strategy encapsula algoritmos variáveis (como cálculo de horas extras e descontos de benefícios) em classes independentes e reutilizáveis, eliminando cadeias gigantes de `if/else`."*

---

### 🎬 4. Método 3: Value Objects (Validações Reutilizáveis)
- Mostre a pasta: [`hrtech_backend_patterns/src/Domain/ValueObjects/`](file:///home/fernando/Documentos/Faculdade/Projeto%20de%20medição%20e%20analise/hrtech_backend_patterns/src/Domain/ValueObjects) (`Cpf.php`, `Money.php`)
- Fala sugerida:
  > *"Utilizamos **Value Objects** como `Cpf` e `Money` para centralizar as regras de validação e formatação. Isso evita que regras de regex ou conversões de moeda fiquem duplicadas em vários pontos do sistema."*

---

### 🎬 5. Método 4: Helpers de API e Frontend
- Mostre a função de resposta no arquivo: [`api.php`](file:///home/fernando/Documentos/Faculdade/Projeto%20de%20medição%20e%20analise/api.php) (`respond()`, `err()`)
- Mostre a função `api()` no arquivo: [`app.js`](file:///home/fernando/Documentos/Faculdade/Projeto%20de%20medição%20e%20analise/app.js)
- Fala sugerida:
  > *"Tanto na API PHP quanto no JavaScript, usamos **Helpers unificados**. A função `api()` do `app.js` abstrai o `fetch()`, tratamento de erros e conversão de JSON para todas as 17 telas do sistema, economizando centenas de linhas de código repetido."*

---

### 🎬 6. Encerramento e Demonstração da Aplicação
- Abra o terminal do VS Code e execute o comando:
  ```bash
  php -S localhost:8000
  ```
- Abra no navegador `http://localhost:8000` e clique em uma tela para provar que a aplicação funciona 100%.
- Fala sugerida:
  > *"Como demonstrado, a compactação por reuso reduziu drasticamente o número de linhas de código mantendo todas as funcionalidades ativas e facilitando a manutenção futura. Obrigado!"*

---

## 📊 Tabela de Métodos Utilizados (Para incluir no Relatório/Vídeo)

| Método / Técnica | Onde foi aplicado | Benefício de Compactação |
| :--- | :--- | :--- |
| **PHP Traits** | `EntityBaseTrait.php` em Entidades | Elimina getters/setters repetidos |
| **Strategy Pattern** | `src/Patterns/Strategy` | Elimina blocos gigantes de `if/else` |
| **Template Method** | `src/Patterns/TemplateMethod` | Reutiliza esqueleto de relatórios/importação |
| **Value Objects** | `Cpf.php`, `Money.php` | Evita duplicação de validações e formatos |
| **API Helper (JS)** | `app.js` (`api()`) | Abstrai requisições `fetch()` para 17 telas |
