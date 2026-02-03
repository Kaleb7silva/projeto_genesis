<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica se veio via POST (proteção contra acesso direto)
//if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//    header('Location: carrinho.php');
//    exit;
//}

// Limpa o carrinho da sessão
unset($_SESSION['carrinho']);

// Limpa o cookie persistente
setcookie('carrinho_persistente', '', time() - 3600, '/', '', false, true);

// Opcional: salvar no banco que a compra foi "simulada"
// (ex: registrar em uma tabela `compras_simuladas`)

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Compra Finalizada - IBDESPERTAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php require_once 'includes/nav.php'; ?>
    <main class="container mt-5 text-center">
        <div class="alert alert-success">
            <h2><i class="fas fa-check-circle"></i> Compra realizada com sucesso!</h2>
            <p class="fs-5">Esta é uma simulação. Nenhum valor foi cobrado.</p>
            <a href="index.php" class="btn btn-primary">Voltar à Página Inicial</a>
            <a href="carrinho.php" class="btn btn-outline-secondary">Ver Carrinho</a>
        </div>
    </main>
    <script src="https://kit.fontawesome.com/..."></script> <!-- se usar Font Awesome -->
</body>
</html>