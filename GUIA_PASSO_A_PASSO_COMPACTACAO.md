# 📋 Roteiro de Gravação: Empacotamento de Código para Reuso

> **Trabalho de Faculdade — Desenvolvimento Orientado a Reuso de Software (Item 03)**  
> **Objetivo:** Demonstrar no terminal o processo de **Empacotamento para Reuso**, transformando o código-fonte e seus artefatos em um componente distribuível reutilizável (Manifesto `composer.json` e arquivo `.phar`).

---

## 📽️ 1. Ferramentas de Gravação (Escolha uma)

- **Gravador de Tela do Windows (Recomendado):**  
  Pressione **`Windows + Alt + R`** (ou `Windows + G` para abrir a Xbox Game Bar) para iniciar a gravação da tela.
- **Gravador de Passos do Windows (PSR):**  
  Pressione `Windows + R`, digite `psr`, dê **Enter** e clique em **Iniciar Captura**.

---

## 🎬 2. Roteiro de Gravação Passo a Passo

### 🎬 1. Apresentação Inicial (30 segundos)
- Abra o repositório no GitHub ou no VS Code.
- **Fala sugerida:**
  > *"Olá! Somos a equipe do projeto de Reuso e Medição de Software. Hoje vamos demonstrar o Item 03 do trabalho: o **Empacotamento de Código para Reuso**, que transforma nosso módulo de software em um artefato distribuível e reutilizável."*

---

### 🎬 2. Explicar o Conceito do Empacotamento (Slide / Teoria)
- Mostre a definição de **Empacotamento para Reuso**:
  > *"O empacotamento não é apenas compactar arquivos em zip; é formalizar o contrato (API), metadados (versão, licença), dependências e preparar o componente para consumo por outros projetos."*

---

### 🎬 3. Mostrar o Manifesto do Pacote (`composer.json`)
- Abra o arquivo: [`hrtech_backend_patterns/composer.json`](file:///d:/Faculdade/Nova%20pasta/HR_Tech/hrtech_backend_patterns/composer.json)
- **Fala sugerida:**
  > *"Formalizamos nosso componente através do manifesto `composer.json`. Definimos o nome do pacote `hrtech/core-patterns`, a versão `1.0.0`, a licença e o mapa de Autoloading PSR-4 para a namespace `HrTech\`."*

---

### 🎬 4. Executar o Comando de Empacotamento no Terminal
- Abra o terminal do VS Code e execute o comando de empacotamento:
  ```bash
  php -d phar.readonly=0 hrtech_backend_patterns/build_package.php
  ```
- Mostre a mensagem de sucesso no terminal:
  `✅ Artefato PHAR gerado com sucesso: dist/hrtech-core.phar`
- **Fala sugerida:**
  > *"Ao executar nosso script de empacotamento no terminal, o PHP compila todo o código-fonte e metadados dentro da pasta `dist/` criando o artefato distribuível `hrtech-core.phar`."*

---

### 🎬 5. Mostrar o Reuso e Padrões no Código
- Abra o arquivo de Trait: [`hrtech_backend_patterns/src/Domain/Traits/EntityBaseTrait.php`](file:///d:/Faculdade/Nova%20pasta/HR_Tech/hrtech_backend_patterns/src/Domain/Traits/EntityBaseTrait.php)
- Abra os Padrões de Projeto: [`hrtech_backend_patterns/src/Patterns/Strategy/`](file:///d:/Faculdade/Nova%20pasta/HR_Tech/hrtech_backend_patterns/src/Patterns/Strategy)
- **Fala sugerida:**
  > *"Dentro do pacote empacotado, o código foi desenvolvido com reutilização: utilizamos **Traits** em PHP para abstrair getters/setters comuns e o padrão **Strategy** para desacoplar regras de negócios."*

---

### 🎬 6. Encerramento e Validação
- Mostre a pasta [`hrtech_backend_patterns/dist/`](file:///d:/Faculdade/Nova%20pasta/HR_Tech/hrtech_backend_patterns/dist) criada com o pacote distribuível pronto para ser utilizado por outros projetos.
- **Fala sugerida:**
  > *"O componente está empacotado, formalizado e pronto para distribuição. Obrigado!"*

---

## 📊 Resumo dos Arquivos do Empacotamento

| Arquivo | Função no Empacotamento |
| :--- | :--- |
| **`composer.json`** | Manifesto oficial com metadados, versão (1.0.0) e contrato PSR-4 |
| **`build_package.php`** | Script de terminal que gera o artefato distribuível (`.phar` e zip) |
| **`dist/hrtech-core.phar`** | Artefato compilado pronto para distribuição e reuso em outros sistemas |
