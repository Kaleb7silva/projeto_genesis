<?php
session_start();

// Verifica se é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: /projeto_genesis/login.php');
    exit;
}

require __DIR__ . '/../config/db.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php?erro=invalido');
    exit;
}

// Verifica se o sorteio está publicado
$stmt_check = $pdo->prepare("SELECT publicado FROM sorteios WHERE id = ?");
$stmt_check->execute([$id]);
$publicado = $stmt_check->fetchColumn();

if ($publicado) {
    $_SESSION['alert'] = [
        'type' => 'warning',
        'message' => 'Não é possível excluir um sorteio publicado. Primeiro, despublique-o.'
    ];
    header('Location: index.php');
    exit;
}

// Busca imagem para excluir do servidor
$stmt = $pdo->prepare("SELECT imagem FROM sorteios WHERE id = ?");
$stmt->execute([$id]);
$imagem = $stmt->fetchColumn();

// Remove imagem do servidor (se não for padrão)
if ($imagem && strpos($imagem, 'default.jpg') === false) {
    $caminho = __DIR__ . '/../' . ltrim($imagem, '/projeto_genesis/');
    if (file_exists($caminho)) {
        unlink($caminho);
    }
}

// Exclui do banco
$pdo->prepare("DELETE FROM sorteios WHERE id = ?")->execute([$id]);

// Redireciona com sucesso
$_SESSION['alert'] = ['type' => 'success', 'message' => 'Sorteio excluído com sucesso!'];
header('Location: index.php');
exit;
?>