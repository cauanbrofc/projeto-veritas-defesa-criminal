# Projeto VERITAS — Defesa Criminal

## Sobre o projeto

O Projeto VERITAS é um protótipo acadêmico de aplicação web desenvolvido como parte de um Trabalho de Conclusão de Curso. O sistema tem como objetivo auxiliar advogados que atuam na defesa criminal na organização de informações relacionadas a casos simulados.

A proposta é reunir, em um único ambiente, registros de casos, pessoas, locais, acontecimentos, evidências, arquivos e alertas para revisão profissional. O sistema também prevê a organização cronológica dos acontecimentos por meio de uma linha do tempo.

O VERITAS é uma ferramenta de apoio à organização da informação. Ele não realiza análise jurídica automatizada, não determina culpa ou inocência, não comprova a autenticidade de evidências e não substitui a atuação profissional do advogado.

## Objetivo acadêmico

Desenvolver um protótipo web capaz de demonstrar recursos básicos de autenticação, controle de acesso e organização de informações relacionadas à defesa criminal, utilizando dados fictícios ou simulados.

## Funcionalidades previstas

- Cadastro de usuário;

- Login e logout;

- Controle de sessão;

- Controle de acesso aos próprios casos;

- Cadastro e listagem de casos;

- Cadastro de pessoas participantes;

- Cadastro de locais;

- Cadastro de acontecimentos;

- Cadastro de evidências;

- Organização cronológica dos registros;

- Envio controlado de arquivos;

- Alertas de possíveis conflitos cadastrais;

- Pesquisa e filtros;

- Relatório resumido do caso;

- Registro de ações importantes em auditoria.

## Tecnologias utilizadas

- PHP puro;

- MySQL;

- MySQL Workbench;

- HTML5;

- CSS3;

- PDO para conexão com o banco de dados;

- XAMPP para execução local do Apache e do PHP;

- Visual Studio Code para desenvolvimento.

## Requisitos para execução local

Antes de executar o projeto, é necessário instalar:

- XAMPP com Apache e PHP;

- MySQL Server;

- MySQL Workbench;

- Visual Studio Code;

- Navegador web.

## Como executar o projeto

### 1. Clonar ou copiar o projeto

Coloque a pasta do projeto dentro do diretório `htdocs` do XAMPP:

```
C:\xampp\htdocs\veritas
```

### 2. Iniciar o Apache

Abra o XAMPP e inicie o módulo **Apache**.

O MySQL Server deve estar instalado e em execução. O MySQL Workbench pode ser utilizado para administrar o banco.

### 3. Criar o banco de dados

Abra o MySQL Workbench e execute o script SQL do projeto para criar o banco `veritas` e suas tabelas.

O banco utilizado pelo sistema é:

```
veritas
```

### 4. Configurar a conexão

Crie o arquivo abaixo a partir do modelo fornecido no projeto:

```
config/conexao.php
```

Ajuste o usuário, a senha e a porta de acordo com a instalação local do MySQL.

> Não publique senhas reais, credenciais ou arquivos de configuração sensíveis no repositório.

### 5. Acessar no navegador

Abra:

```
http://localhost/veritas/
```

## Estrutura inicial

```
veritas/
├── assets/
│   └── css/
│       └── style.css
├── config/
│   └── conexao.php
├── views/
│   ├── cadastro.php
│   ├── casos.php
│   ├── home.php
│   ├── layout.php
│   ├── login.php
│   ├── painel.php
│   └── ...
├── index.php
└── README.md
```

## Segurança e limitações

O protótipo utiliza dados fictícios ou simulados. Não devem ser inseridos no sistema dados reais de clientes, acusados, vítimas, testemunhas ou processos criminais.

As senhas devem ser armazenadas utilizando funções próprias de proteção, como `password_hash( )`, e verificadas com `password_verify()`.

O sistema não possui integração com tribunais, delegacias, escritórios de advocacia, inquéritos policiais ou bases oficiais de processos.

O armazenamento de arquivos possui finalidade organizacional. O sistema não implementa perícia digital, cadeia de custódia ou validação da autenticidade jurídica de documentos.

## Situação atual do desenvolvimento

Até o momento, foram implementados ou preparados:

- identidade visual do protótipo;

- conexão com o banco MySQL;

- cadastro de usuário;

- login;

- logout;

- controle de sessão;

- proteção de páginas internas;

- painel inicial do advogado;

- cadastro e listagem de casos.

As demais funcionalidades serão implementadas gradualmente e avaliadas por meio de testes funcionais.

## Projeto acadêmico

Este repositório faz parte do desenvolvimento do Trabalho de Conclusão de Curso e possui finalidade acadêmica e demonstrativa.

**Autor:** [Seu nome completo]
**Curso:** [Nome do curso]
**Instituição:** [Nome da instituição]
**Ano:** 2026
