<?php
require __DIR__ . '/config.php';
date_default_timezone_set('America/Sao_Paulo');
header('Cache-Control: no-store');

// A conexão db() e a sessão vêm do config.php.
function ir(string $pagina): void
{
    header('Location: index.php?pagina=' . $pagina);
    exit;
}

function campo(string $nome): string
{
    return is_string($_POST[$nome] ?? null) ? $_POST[$nome] : '';
}

function mensagem(string $texto, string $tipo = 'sucesso'): void
{
    $_SESSION['aviso_vetcare'] = $texto;
    $_SESSION['tipo_aviso_vetcare'] = $tipo;
}

// Conta caracteres acentuados sem depender da extensão mbstring.
function tamanho(string $texto): int
{
    $quantidade = preg_match_all('/./us', $texto);
    return $quantidade === false ? PHP_INT_MAX : $quantidade;
}

$pagina = $_GET['pagina'] ?? 'login';
if (!in_array($pagina, ['login', 'painel', 'usuarios', 'agendamentos'], true)) {
    $pagina = 'login';
}
if (in_array($pagina, ['painel', 'usuarios', 'agendamentos'], true) && !logged_in()) {
    ir('login');
}
if ($pagina === 'login' && logged_in()) {
    ir('painel');
}

$_SESSION['token_vetcare'] ??= bin2hex(random_bytes(32));
$token = $_SESSION['token_vetcare'];
$erro = '';
$editar = null;
$id = 0;
$registros = [];
$proximos = [];
$falhaBanco = false;
$statusLista = ['Agendado', 'Confirmado', 'Concluído', 'Cancelado'];
$metodoPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if (in_array($pagina, ['usuarios', 'agendamentos'], true)) {
    $id = filter_var($metodoPost ? campo('id') : ($_GET['editar'] ?? 0), FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0],
    ]);
    if ($id === false) {
        mensagem('Registro inválido.', 'erro');
        ir($pagina);
    }
}

if ($metodoPost) {
    if (!hash_equals($token, campo('token'))) {
        http_response_code(400);
        $erro = 'Este formulário expirou. Confira os dados e tente novamente.';
        $metodoPost = false; // Não executa nenhuma alteração com token inválido.
    }
}

if ($metodoPost) {
    if ($pagina === 'painel' && campo('acao') === 'sair') {
        $_SESSION = [];
        session_regenerate_id(true);
        mensagem('Você saiu do sistema.');
        ir('login');
    }
}

try {
// Uma conta excluída não deve continuar acessando por uma sessão antiga.
if (logged_in()) {
    $consulta = db()->prepare('SELECT nome FROM usuarios WHERE id = ?');
    $consulta->execute([$_SESSION['usuario']['id']]);
    $contaAtual = $consulta->fetch();
    if (!$contaAtual) {
        $_SESSION = [];
        session_regenerate_id(true);
        ir('login');
    }
    $_SESSION['usuario']['nome'] = $contaAtual['nome'];
}

if ($metodoPost) {
    if ($pagina === 'login') {
        $consulta = db()->prepare('SELECT id, nome, senha FROM usuarios WHERE usuario = ?');
        $consulta->execute([trim(campo('usuario'))]);
        $conta = $consulta->fetch();
        if ($conta && password_verify(campo('senha'), $conta['senha'])) {
            session_regenerate_id(true);
            $_SESSION['token_vetcare'] = bin2hex(random_bytes(32));
            $_SESSION['usuario'] = ['id' => $conta['id'], 'nome' => $conta['nome']];
            mensagem('Login realizado. Bem-vindo(a)!');
            ir('painel');
        }
        $erro = 'Usuário ou senha inválidos.';
    }
}

if (in_array($pagina, ['usuarios', 'agendamentos'], true)) {
    // A página foi validada acima: só pode ser um destes dois nomes de tabela.
    $tabela = $pagina;
    $registro = null;
    if ($id > 0) {
        $consulta = db()->prepare("SELECT * FROM $tabela WHERE id = ?");
        $consulta->execute([$id]);
        $registro = $consulta->fetch();
        if (!$registro) {
            mensagem('Registro não encontrado.', 'erro');
            ir($pagina);
        }
        $editar = $registro;
    }

    if ($metodoPost && (!in_array(campo('acao'), ['salvar', 'excluir'], true)
        || (campo('acao') === 'excluir' && !$registro))) {
        $erro = 'Ação inválida. Selecione um registro e tente novamente.';
        $metodoPost = false;
    }

    if ($metodoPost && campo('acao') === 'excluir' && $registro) {
        if ($pagina === 'usuarios' && $id === (int) $_SESSION['usuario']['id']) {
            mensagem('Você não pode excluir o usuário que está usando.', 'erro');
        } else {
            $consulta = db()->prepare("DELETE FROM $tabela WHERE id = ?");
            $consulta->execute([$id]);
            $excluido = $consulta->rowCount() > 0;
            mensagem($excluido ? ($pagina === 'usuarios' ? 'Usuário excluído com sucesso.' : 'Agendamento excluído com sucesso.') : 'Registro não encontrado.', $excluido ? 'sucesso' : 'erro');
        }
        ir($pagina);
    }

    if ($metodoPost && campo('acao') === 'salvar') {
        if ($pagina === 'usuarios') {
            $nome = trim(campo('nome'));
            $usuario = trim(campo('usuario'));
            $senha = campo('senha');
            $editar = ['nome' => $nome, 'usuario' => $usuario];

            if ($nome === '' || $usuario === '' || tamanho($nome) > 100 || tamanho($usuario) > 50) {
                $erro = 'Preencha o nome e o usuário dentro dos limites do formulário.';
            } elseif ((!$id || $senha !== '') && (tamanho($senha) < 6 || tamanho($senha) === PHP_INT_MAX || strlen($senha) > 72 || strpos($senha, "\0") !== false)) {
                $erro = 'Use pelo menos 6 caracteres na senha. Se ela for muito longa, reduza-a: acentos e símbolos ocupam mais espaço.';
            } else {
                try {
                    if (!$id) {
                        $consulta = db()->prepare('INSERT INTO usuarios (nome, usuario, senha) VALUES (?, ?, ?)');
                        $consulta->execute([$nome, $usuario, password_hash($senha, PASSWORD_DEFAULT)]);
                    } elseif ($senha === '') {
                        $consulta = db()->prepare('UPDATE usuarios SET nome = ?, usuario = ? WHERE id = ?');
                        $consulta->execute([$nome, $usuario, $id]);
                    } else {
                        $consulta = db()->prepare('UPDATE usuarios SET nome = ?, usuario = ?, senha = ? WHERE id = ?');
                        $consulta->execute([$nome, $usuario, password_hash($senha, PASSWORD_DEFAULT), $id]);
                    }
                    if ($id === (int) $_SESSION['usuario']['id']) {
                        $_SESSION['usuario']['nome'] = $nome;
                    }
                } catch (PDOException $excecao) {
                    if ((int) ($excecao->errorInfo[1] ?? 0) !== 1062) {
                        throw $excecao;
                    }
                    $erro = 'Esse nome de usuário já está em uso.';
                }
            }
        } else {
            $tutor = trim(campo('nome_tutor'));
            $pet = trim(campo('nome_pet'));
            $servico = trim(campo('servico'));
            $horario = campo('data_hora');
            $status = campo('status');
            $observacoes = trim(campo('observacoes'));
            $editar = ['nome_tutor' => $tutor, 'nome_pet' => $pet, 'servico' => $servico,
                'data_hora' => $horario, 'status' => $status, 'observacoes' => $observacoes];
            $data = preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}$/D', $horario)
                ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $horario)
                : false;
            if ($tutor === '' || $pet === '' || $servico === ''
                || max(tamanho($tutor), tamanho($pet), tamanho($servico)) > 100
                || !$data || $data->format('Y-m-d\TH:i') !== $horario
                || !in_array($status, $statusLista, true) || tamanho($observacoes) > 1000) {
                $erro = 'Confira os campos, a data, o horário e o status.';
            } else {
                $valores = [$tutor, $pet, $servico, $data->format('Y-m-d H:i:s'), $status, $observacoes];
                if (!$id) {
                    $consulta = db()->prepare('INSERT INTO agendamentos (nome_tutor, nome_pet, servico, data_hora, status, observacoes) VALUES (?, ?, ?, ?, ?, ?)');
                } else {
                    $consulta = db()->prepare('UPDATE agendamentos SET nome_tutor = ?, nome_pet = ?, servico = ?, data_hora = ?, status = ?, observacoes = ? WHERE id = ?');
                    $valores[] = $id;
                }
                $consulta->execute($valores);
            }
        }

        if ($erro === '') {
            $itemNome = $pagina === 'usuarios' ? 'Usuário' : 'Agendamento';
            mensagem($itemNome . ($id ? ' alterado com sucesso.' : ' cadastrado com sucesso.'));
            ir($pagina);
        }
    }

    $registros = $pagina === 'usuarios'
        ? db()->query('SELECT id, nome, usuario FROM usuarios ORDER BY nome')->fetchAll()
        : db()->query('SELECT * FROM agendamentos ORDER BY data_hora DESC')->fetchAll();
}

if ($pagina === 'painel') {
    $agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
    $consulta = db()->prepare("SELECT nome_pet, nome_tutor, servico, data_hora, status
        FROM agendamentos WHERE data_hora >= ? AND status IN ('Agendado', 'Confirmado')
        ORDER BY data_hora, id LIMIT 5");
    $consulta->execute([$agora->format('Y-m-d H:i:s')]);
    $proximos = $consulta->fetchAll();
}
} catch (PDOException $excecao) {
    http_response_code(503);
    $falhaBanco = true;
    $erro = 'Não foi possível acessar o banco de dados. Tente novamente em alguns instantes.';
}

// Conserva os campos para corrigir e reenviar; senhas nunca voltam ao HTML.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && campo('acao') === 'salvar') {
    $editar = [];
    foreach (['nome', 'usuario', 'nome_tutor', 'nome_pet', 'servico', 'data_hora', 'status', 'observacoes'] as $chave) {
        $editar[$chave] = campo($chave);
    }
}

$titulos = ['login' => 'Entrar', 'painel' => 'Painel', 'usuarios' => 'Usuários', 'agendamentos' => 'Agendamentos'];
$statusClasses = ['Agendado' => 'pendente', 'Confirmado' => 'confirmado', 'Concluído' => 'concluido', 'Cancelado' => 'cancelado'];
$aviso = $_SESSION['aviso_vetcare'] ?? '';
$tipoAviso = ($_SESSION['tipo_aviso_vetcare'] ?? '') === 'erro' ? 'erro' : 'sucesso';
unset($_SESSION['aviso_vetcare'], $_SESSION['tipo_aviso_vetcare']);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulos[$pagina]) ?> | Pata Nobre</title>
    <script src="feedback.js?v=1" defer></script>
    <meta name="theme-color" content="#29231f">
    <link rel="stylesheet" href="vetcare.css?v=feedback-1">
</head>
<body class="<?= $pagina === 'login' ? 'tela-login' : 'aplicativo' ?>">
<svg class="icones" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <symbol id="icone-painel" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
    <symbol id="icone-agendamentos" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18m-13 5h3m2 0h3"/></symbol>
    <symbol id="icone-usuarios" viewBox="0 0 24 24"><circle cx="10" cy="7" r="3"/><path d="M3 21v-3a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v3m1-17a3 3 0 0 1 0 6m2 4a4 4 0 0 1 2 4v3"/></symbol>
    <symbol id="icone-sair" viewBox="0 0 24 24"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5m4-12 4 4-4 4m-7-4h11"/></symbol>
</svg>

<?php if ($pagina !== 'login'): ?>
<aside class="barra">
    <a class="marca" href="?pagina=painel"><img class="logo-menu" src="logo-pata-nobre.png" alt="Pata Nobre Pet Shop" width="1536" height="1024"></a>
    <nav aria-label="Menu principal">
        <?php foreach (['painel', 'agendamentos', 'usuarios'] as $secao): ?>
            <a href="?pagina=<?= $secao ?>"<?= $pagina === $secao ? ' aria-current="page"' : '' ?>>
                <svg aria-hidden="true"><use href="#icone-<?= $secao ?>"/></svg><?= e($titulos[$secao]) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <form method="post" action="?pagina=painel" class="sessao">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <span class="conta"><?= e($_SESSION['usuario']['nome']) ?></span>
        <button name="acao" value="sair" class="sair"><svg aria-hidden="true"><use href="#icone-sair"/></svg>Sair</button>
    </form>
</aside>
<?php endif; ?>

<div class="feedback-area" aria-label="Notificações">
    <?php if ($aviso !== ''): ?>
        <div class="feedback" data-tipo="<?= $tipoAviso ?>" role="<?= $tipoAviso === 'erro' ? 'alert' : 'status' ?>">
            <span><?= e($aviso) ?></span>
            <button type="button" class="feedback-fechar" aria-label="Fechar notificação">×</button>
        </div>
    <?php endif; ?>
</div>
<main id="conteudo">
    <?php if ($pagina === 'login'): ?>
        <section class="acesso" aria-labelledby="titulo-acesso">
            <img class="logo-login" src="logo-pata-nobre.png" alt="Pata Nobre Pet Shop" width="1536" height="1024">
            <h1 id="titulo-acesso">Entrar no sistema</h1>
            <p class="descricao">Use sua conta para acessar o painel.</p>
            <?php if ($erro !== ''): ?><p class="erro" role="alert"><?= e($erro) ?></p><?php endif; ?>
            <form method="post" action="?pagina=login" class="formulario">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label>Usuário <input name="usuario" autocomplete="username" maxlength="50" required value="<?= e(campo('usuario')) ?>"></label>
                <label>Senha <input name="senha" type="password" autocomplete="current-password" required></label>
                <button>Entrar</button>
            </form>
        </section>
    <?php elseif ($pagina === 'painel'): ?>
        <header class="cabecalho">
            <div><h1>Painel</h1><p class="descricao">Olá, <?= e($_SESSION['usuario']['nome']) ?>.</p></div>
            <a class="botao" href="?pagina=agendamentos#formulario">Novo agendamento</a>
        </header>
        <?php if ($erro !== ''): ?><p class="erro" role="alert"><?= e($erro) ?></p><?php endif; ?>
        <section class="quadro" aria-labelledby="proximos">
            <div class="quadro-topo"><h2 id="proximos">Próximos agendamentos</h2><a href="?pagina=agendamentos">Ver agenda</a></div>
            <?php if ($falhaBanco): ?>
                <p class="vazio">Os horários não puderam ser carregados. Atualize a página para tentar novamente.</p>
            <?php elseif (!$proximos): ?>
                <p class="vazio">Nenhum horário futuro agendado.</p>
            <?php else: ?>
                <div class="rolagem" role="region" aria-label="Próximos agendamentos" tabindex="0">
                    <table>
                        <thead><tr><th scope="col">Data e hora</th><th scope="col">Pet e tutor</th><th scope="col">Serviço</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($proximos as $item): ?>
                            <tr>
                                <td class="data"><?= date('d/m/Y H:i', strtotime($item['data_hora'])) ?></td>
                                <td><strong><?= e($item['nome_pet']) ?></strong><small><?= e($item['nome_tutor']) ?></small></td>
                                <td><?= e($item['servico']) ?></td>
                                <td><span class="status <?= $statusClasses[$item['status']] ?? '' ?>"><?= e($item['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <?php $agenda = $pagina === 'agendamentos'; ?>
        <header class="cabecalho">
            <div><h1><?= e($titulos[$pagina]) ?></h1><p class="descricao"><?= $agenda ? 'Gerencie os horários e os serviços dos pets.' : 'Gerencie as contas da equipe.' ?></p></div>
        </header>
        <?php if ($erro !== ''): ?><p class="erro" role="alert"><?= e($erro) ?></p><?php endif; ?>
        <div class="registros">
        <section class="quadro cadastro" aria-labelledby="formulario">
        <h2 id="formulario"><?= $id ? ($agenda ? 'Editar agendamento' : 'Editar usuário') : ($agenda ? 'Novo agendamento' : 'Novo usuário') ?></h2>
        <form method="post" action="?pagina=<?= $pagina ?>" class="formulario">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <?php if ($agenda): ?>
                <label>Nome do tutor <input name="nome_tutor" maxlength="100" required value="<?= e($editar['nome_tutor'] ?? '') ?>"></label>
                <label>Nome do pet <input name="nome_pet" maxlength="100" required value="<?= e($editar['nome_pet'] ?? '') ?>"></label>
                <label>Serviço <input name="servico" maxlength="100" placeholder="Ex.: Banho e tosa" required value="<?= e($editar['servico'] ?? '') ?>"></label>
                <label>Data e horário <input type="datetime-local" name="data_hora" required value="<?= e(str_replace(' ', 'T', substr($editar['data_hora'] ?? '', 0, 16))) ?>"></label>
                <label>Status
                    <select name="status">
                        <?php foreach ($statusLista as $status): ?>
                            <option<?= $status === ($editar['status'] ?? 'Agendado') ? ' selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Observações (opcional) <textarea name="observacoes" rows="2" maxlength="1000"><?= e($editar['observacoes'] ?? '') ?></textarea></label>
            <?php else: ?>
                <label>Nome <input name="nome" maxlength="100" required value="<?= e($editar['nome'] ?? '') ?>"></label>
                <label>Usuário <input name="usuario" maxlength="50" required value="<?= e($editar['usuario'] ?? '') ?>"></label>
                <label><?= $id ? 'Nova senha (opcional)' : 'Senha (mínimo de 6 caracteres)' ?>
                    <input type="password" name="senha" autocomplete="new-password" minlength="6" maxlength="72" <?= $id ? '' : 'required' ?>>
                </label>
            <?php endif; ?>
            <div class="acoes">
                <button name="acao" value="salvar" <?= $falhaBanco ? 'disabled' : '' ?>><?= $id ? 'Salvar alterações' : 'Cadastrar' ?></button>
                <?php if ($id): ?><a href="?pagina=<?= $pagina ?>" data-cancelar-edicao>Cancelar</a><?php endif; ?>
            </div>
        </form>

        </section>
        <section class="quadro lista" aria-labelledby="titulo-lista">
        <div class="quadro-topo"><h2 id="titulo-lista"><?= $agenda ? 'Horários cadastrados' : 'Usuários cadastrados' ?></h2><span class="contagem"><?= $falhaBanco ? '—' : count($registros) ?></span></div>
        <?php if ($falhaBanco): ?>
            <p class="vazio">A lista não pôde ser carregada. Atualize a página para tentar novamente.</p>
        <?php elseif (!$registros): ?>
            <p class="vazio"><?= $agenda ? 'Nenhum agendamento cadastrado.' : 'Nenhum usuário cadastrado.' ?></p>
        <?php else: ?>
            <div class="rolagem" role="region" aria-label="Lista de registros" tabindex="0">
                <table>
                    <thead>
                    <?php if ($agenda): ?>
                        <tr><th>Data e hora</th><th>Pet e tutor</th><th>Serviço</th><th>Status</th><th>Ações</th></tr>
                    <?php else: ?>
                        <tr><th>Nome</th><th>Usuário</th><th>Ações</th></tr>
                    <?php endif; ?>
                    </thead>
                    <tbody>
                    <?php foreach ($registros as $item): ?>
                        <tr>
                            <?php if ($agenda): ?>
                                <td class="data"><?= date('d/m/Y H:i', strtotime($item['data_hora'])) ?></td>
                                <td><strong><?= e($item['nome_pet']) ?></strong><small><?= e($item['nome_tutor']) ?></small></td>
                                <td><?= e($item['servico']) ?></td>
                                <td><span class="status <?= $statusClasses[$item['status']] ?? '' ?>"><?= e($item['status']) ?></span></td>
                            <?php else: ?>
                                <td><?= e($item['nome']) ?></td><td><?= e($item['usuario']) ?></td>
                            <?php endif; ?>
                            <td class="acoes">
                                <a href="?pagina=<?= $pagina ?>&amp;editar=<?= (int) $item['id'] ?>#formulario">Editar</a>
                                <?php if ($agenda || (int) $item['id'] !== (int) $_SESSION['usuario']['id']): ?>
                                    <form method="post" action="?pagina=<?= $pagina ?>" onsubmit="return window.confirmarExclusao ? window.confirmarExclusao() : confirm('Confirma a exclusão deste registro?')">
                                        <input type="hidden" name="token" value="<?= e($token) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button class="excluir" name="acao" value="excluir">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        </section>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
