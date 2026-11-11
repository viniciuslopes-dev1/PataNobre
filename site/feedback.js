// Os avisos de sucesso somem após cinco segundos. Erros aguardam fechamento.
const area = document.querySelector('.feedback-area');

function prepararAviso(aviso) {
    let tempo;
    const fechar = () => aviso.remove();
    const pausar = () => clearTimeout(tempo);
    const iniciar = () => {
        pausar();
        if (aviso.dataset.tipo !== 'erro') tempo = setTimeout(fechar, 5000);
    };
    aviso.querySelector('button').addEventListener('click', fechar);
    aviso.addEventListener('mouseenter', pausar);
    aviso.addEventListener('mouseleave', iniciar);
    aviso.addEventListener('focusin', pausar);
    aviso.addEventListener('focusout', iniciar);
    iniciar();
}

function informar(texto) {
    const aviso = document.createElement('div');
    aviso.className = 'feedback';
    aviso.dataset.tipo = 'info';
    aviso.setAttribute('role', 'status');
    const mensagem = document.createElement('span');
    mensagem.textContent = texto;
    const fechar = document.createElement('button');
    fechar.type = 'button';
    fechar.className = 'feedback-fechar';
    fechar.setAttribute('aria-label', 'Fechar notificação');
    fechar.textContent = '×';
    aviso.append(mensagem, fechar);
    area.replaceChildren(aviso);
    prepararAviso(aviso);
}

document.querySelectorAll('.feedback').forEach(prepararAviso);
window.confirmarExclusao = function () {
    const confirmado = confirm('Confirma a exclusão deste registro?');
    if (!confirmado) informar('Exclusão cancelada. O registro foi mantido.');
    return confirmado;
};

// Leva o aviso de cancelamento para a página seguinte, sem salvar dados do cadastro.
document.querySelectorAll('[data-cancelar-edicao]').forEach(link => {
    link.addEventListener('click', () => {
        try { sessionStorage.setItem('pata-edicao-cancelada', '1'); } catch (_) {}
    });
});
try {
    if (sessionStorage.getItem('pata-edicao-cancelada')) {
        sessionStorage.removeItem('pata-edicao-cancelada');
        informar('Edição cancelada. Nenhuma alteração foi salva.');
    }
} catch (_) {}
