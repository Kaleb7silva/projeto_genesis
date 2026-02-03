<?php
// Verifica se o usuário está logado e é admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: /projeto_genesis/index.php');
    exit;
}
?>

<?php require_once __DIR__ . '/includes/nav.php'; ?>

<div class="container mt-4">
    <h2>➕ Cadastrar Novo Membro</h2>
    <form method="POST" action="processar_cadastro.php" enctype="multipart/form-data">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nome Completo</label>
                <input type="text" name="nome" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">CPF (apenas números)</label>
                <input type="text" name="cpf" class="form-control" maxlength="11" pattern="\d{11}"
                    title="Digite 11 números" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Data de Nascimento</label>
                <input type="text" name="data_nascimento" class="form-control" id="data_nascimento"
                    placeholder="DD/MM/AAAA" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Gênero</label>
                <select name="genero" class="form-select" required>
                    <option value="">-- Selecione --</option>
                    <option value="masculino">Masculino</option>
                    <option value="feminino">Feminino</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Batizado?</label>
                <select name="batizado" class="form-select" required>
                    <option value="">-- Selecione --</option>
                    <option value="sim">Sim</option>
                    <option value="nao">Não</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                    <option value="suspenso">Suspenso</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Foto de Perfil (opcional)</label>
                <input type="file" name="foto_perfil" class="form-control" accept="image/*">
                <small class="form-text text-muted">Deixe em branco para usar foto padrão.</small>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Cadastrar</button>
            <a href="admin_membros.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!-- Locale em português -->
<script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr("#data_nascimento", {
            locale: "pt",
            dateFormat: "d/m/Y",
            maxDate: "today",
            allowInput: true
        });
    });
</script>