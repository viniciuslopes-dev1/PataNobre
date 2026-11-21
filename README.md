# Pata Nobre

Projeto acadêmico de um sistema web para gerenciamento de um pet shop.

O sistema tem uma página inicial com os serviços do pet shop e uma área restrita, onde a equipe organiza os agendamentos e o administrador gerencia os usuários com acesso à aplicação.

## Funcionalidades

- Página inicial com os serviços oferecidos e botão "Área do funcionário" para o login.
- Login com usuário e senha.
- Dois perfis de acesso: administrador e funcionário. Somente o administrador cadastra, edita e exclui usuários.
- Painel com os próximos agendamentos.
- Cadastro, consulta, edição e exclusão de agendamentos.
- Lista de serviços para escolha no agendamento: banho, tosa, banho e tosa, corte de unhas, hidratação, limpeza de ouvidos e escovação de dentes.
- Bloqueio de dois agendamentos no mesmo horário (agendamentos cancelados não ocupam o horário).
- Controle do status dos atendimentos.
- Confirmação de exclusão e mensagens de retorno das operações.
- Encerramento de sessão.

## Tecnologias

PHP, MySQL, HTML, CSS e JavaScript. A comunicação com o banco de dados utiliza PDO.

## Arquivos

- `index.php`: processamento das requisições e telas do sistema.
- `estilo.css`: estilos da interface.
- `feedback.js`: notificações e confirmação de exclusão.
- `logo-pata-nobre.png`: logo do pet shop.
- `tabelas.sql`: estrutura das cinco tabelas do projeto.

## Banco de dados

O sistema utiliza as tabelas `usuarios` e `agendamentos`. A coluna `usuarios.admin` indica se a conta é de administrador (1) ou de funcionário (0). As tabelas `servicos`, `produtos` e `tutores` completam a estrutura do projeto e estão previstas para ampliação.

Em um banco criado antes do perfil de administrador, execute uma única vez:

```sql
ALTER TABLE usuarios ADD COLUMN admin TINYINT(1) NOT NULL DEFAULT 0 AFTER senha;
UPDATE usuarios SET admin = 1 ORDER BY id LIMIT 1;
```

A aplicação utiliza um arquivo `config.php` com a conexão ao banco e as funções de sessão. Esse arquivo é configurado no ambiente de execução e não é incluído no repositório por conter credenciais.
