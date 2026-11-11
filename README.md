# Pata Nobre

Sistema web para gerenciar usuários e agendamentos de um pet shop.

## Tecnologias

PHP 8 ou superior, PDO MySQL, MySQL ou MariaDB, HTML, CSS e JavaScript.

## Funcionalidades

- Login e encerramento de sessão.
- Cadastro, consulta, edição e exclusão de usuários e agendamentos.
- Confirmação antes de excluir registros.
- Painel com os cinco próximos agendamentos ativos.
- Validação de formulários e notificações após as operações.

## Estrutura

- `site/index.php`: processamento das requisições e geração das telas.
- `site/vetcare.css`: estilos da interface.
- `site/feedback.js`: notificações e confirmação de exclusão.
- `site/logo-pata-nobre.png`: identidade visual.
- `instalacao/config.exemplo.php`: modelo da conexão com o banco.
- `instalacao/database.sql`: estrutura das duas tabelas utilizadas pelo sistema.
- `instalacao/estrutura-completa.sql`: estrutura de cinco tabelas prevista na documentação.
- `instalacao/primeiro-usuario.php`: utilitário de terminal para gerar o cadastro inicial.

## Instalação

1. Crie um banco MySQL e selecione-o no phpMyAdmin.
2. Importe `instalacao/database.sql`.
3. Copie `instalacao/config.exemplo.php` para `site/config.php`.
4. Configure `DB_HOST`, `DB_NAME`, `DB_USER` e `DB_PASS` conforme o ambiente.
5. Execute `php instalacao/primeiro-usuario.php` em um terminal com PHP. Informe os dados da conta e execute o INSERT gerado no banco selecionado.
6. Configure a pasta `site` como diretório público da aplicação.

Para executar localmente com o servidor de desenvolvimento do PHP:

```bash
php -S localhost:8000 -t site
```

Acesse `http://localhost:8000`. O MySQL deve estar em execução.

Em hospedagem compartilhada, envie o conteúdo de `site` para o diretório público e configure o `config.php` nesse ambiente. Mantenha os utilitários e arquivos SQL fora do diretório público.

## Banco de dados

O CRUD utiliza as tabelas `usuarios` e `agendamentos`. A estrutura completa acrescenta `servicos`, `produtos` e `tutores`, previstas para ampliação, ainda sem telas ou relacionamentos implementados.

Os dois arquivos SQL são alternativas de criação para um banco vazio. Não importe ambos no mesmo banco nem os execute como atualização de uma instalação existente.

## Atualização

Em uma instalação existente, substitua os arquivos de interface e processamento e preserve o `config.php` e os dados atuais. Não reimporte o SQL.

As credenciais reais de conexão não fazem parte deste repositório.
