$(document).ready(function() {
    // Confirmar exclusão
    $('.btn-delete').click(function(e) {
        if(!confirm('Tem certeza que deseja excluir este registro?')) {
            e.preventDefault();
        }
    });

    // Máscaras
    $('.cpf').mask('000.000.000-00');
    $('.cnpj').mask('00.000.000/0000-00');
    $('.telefone').mask('(00) 00000-0000');
    $('.money').mask('000.000.000,00', {reverse: true});
});

// Funções utilitárias
function showAlert(message, type = 'success') {
    const alert = $(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    $('.main-content').prepend(alert);
    setTimeout(() => alert.alert('close'), 3000);
}