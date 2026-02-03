<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define perfil com fallback
$perfil = $_SESSION['perfil'] ?? 'membro';

// Pega o nome completo do usuário (se estiver disponível)
$nome_completo = $_SESSION['nome'] ?? 'Usuário';
$primeiro_nome = explode(' ', $nome_completo)[0] ?? 'Usuário';

// Define caminho da foto de perfil com fallback
$foto_perfil = $_SESSION['foto_perfil'] ?? 'default.png';
$caminho_foto = '/projeto_genesis/uploads/fotos/' . htmlspecialchars($foto_perfil);

// Verifica se o arquivo existe, senão usa fallback
$fallback_foto = '/projeto_genesis/img/utilizador-de-negocios-em-cog_78370-7040.jpg';
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $caminho_foto)) {
    $caminho_foto = $fallback_foto;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>IBDESPERTAR</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Estilos personalizados -->
    <!-- 🔥 Caminho ABSOLUTO → FUNCIONA EM TODAS AS PASTAS -->
    <link rel="stylesheet" href="/projeto_genesis/style.css">
</head>

<body>

    <!-- CABEÇALHO PRINCIPAL (AZUL) -->
    <header class="bg-primary text-white py-2 shadow-sm">
        <div class="container-fluid d-flex justify-content-between align-items-center">

            <!-- Logo + Nome -->
            <div class="d-flex align-items-center">
                <img src="/projeto_genesis/img/PERFIL_SIMBOLO_BRANCO_PNG.png" alt="Logo IBDESPERTAR"
                    class="rounded-circle me-3" style="width: 40px; height: 40px;">
                <h4 class="mb-0 fw-bold">IBDESPERTAR</h4>
            </div>

            <!-- Botões à Direita -->
            <div class="d-flex gap-2">

                <!-- Carrinho -->
                <a href="/projeto_genesis/carrinho.php"
                    class="btn btn-outline-light btn-sm rounded-pill px-3 d-flex align-items-center">
                    <i class="fas fa-shopping-cart me-1"></i>Meu Carrinho
                </a>

                <!-- Dropdown do Usuário -->
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center gap-2 btn-sm"
                        type="button" data-bs-toggle="dropdown" aria-expanded="false">

                        <img src="<?= $caminho_foto ?>" class="rounded-circle"
                            alt="Foto de <?= htmlspecialchars($primeiro_nome) ?>"
                            style="width: 30px; height: 30px; object-fit: cover;">

                        <span><?= htmlspecialchars($primeiro_nome) ?></span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/projeto_genesis/meu_perfil.php">
                                <i class="fas fa-user me-2"></i>Meu Perfil</a></li>

                        <!--   <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configurações</a></li> -->

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li><a class="dropdown-item text-danger" href="/projeto_genesis/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- MENU SECUNDÁRIO (CINZA) -->
    <nav class="bg-secondary text-white py-2 shadow-sm sticky-top">
        <div class="container-fluid d-flex justify-content-between align-items-center">

            <!-- Início -->
            <a href="/projeto_genesis/index.php"
                class="btn btn-outline-light btn-sm rounded-pill px-3 d-flex align-items-center gap-1">
                <i class="fas fa-home"></i>Início
            </a>

            <div class="d-flex gap-2">

                <?php if ($perfil === 'membro'): ?>
                    <a href="/projeto_genesis/agendamento.php" class="btn btn-outline-info btn-sm rounded-pill px-2">
                        <i class="fas fa-calendar-check me-1"></i>Meu Agendamento
                    </a>

                    <a href="/projeto_genesis/meus_pedidos.php" class="btn btn-outline btn-sm rounded-pill px-2 ">
                        <i class="fas fa-history me-1"></i>Meu Histórico
                    </a>
                <?php endif; ?>

                <?php if ($perfil === 'admin'): ?>
                    <a href="/projeto_genesis/admin_membros.php" class="btn btn-outline-info btn-sm rounded-pill px-3">
                        <i class="fas fa-users me-1"></i>
                    </a>

                    <a href="/projeto_genesis/admin_horarios.php" class="btn btn-outline-info btn-sm rounded-pill px-3">
                        <i class="fas fa-clock me-1"></i>Horários
                    </a>

                    <a href="/projeto_genesis/sorteios/index.php" class="btn btn-outline-purple btn-sm rounded-pill px-3">
                        <i class="fas fa-gift me-1"></i>Sorteios
                    </a>

                    <a href="/projeto_genesis/eventos/index.php" class="btn btn-outline-success btn-sm rounded-pill px-3">
                        <i class="fas fa-calendar-alt me-1"></i>Eventos
                    </a>

                    <a href="/projeto_genesis/publicacoes/index.php"
                        class="btn btn-outline-warning btn-sm rounded-pill px-3">
                        <i class="fas fa-file-alt me-1"></i>Publicações
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </nav>