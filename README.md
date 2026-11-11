# Pata Nobre

Projeto acadêmico de um sistema web para gerenciamento de um pet shop.

O sistema permite que a equipe organize os agendamentos de serviços e gerencie os usuários com acesso à aplicação.

## Funcionalidades

- Login com usuário e senha.
- Painel com os próximos agendamentos.
- Cadastro, consulta, edição e exclusão de agendamentos e usuários.
- Controle do status dos atendimentos.
- Confirmação de exclusão e mensagens de retorno das operações.
- Encerramento de sessão.

## Tecnologias

PHP, MySQL, HTML, CSS e JavaScript. A comunicação com o banco de dados utiliza PDO.

## Arquivos

- `index.php`: processamento das requisições e telas do sistema.
- `vetcare.css`: estilos da interface.
- `feedback.js`: notificações e confirmação de exclusão.
- `logo-pata-nobre.png`: logo do pet shop.

A aplicação utiliza um arquivo `config.php` com a conexão ao banco e as funções de sessão. Esse arquivo é configurado no ambiente de execução e não é incluído no repositório por conter credenciais.
