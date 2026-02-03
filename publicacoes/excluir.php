<?php
require __DIR__ . '/../includes/nav.php';

if ($perfil !== 'admin') exit;

require __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT arquivo FROM publicacoes WHERE id = ?");
$stmt->execute([$id]);
$arquivo = $stmt->fetchColumn();

if ($arquivo && file_exists("../" . ltrim($arquivo, '/projeto_genesis/'))) {
    unlink("../" . ltrim($arquivo, '/projeto_genesis/'));
}

$pdo->prepare("DELETE FROM publicacoes WHERE id = ?")->execute([$id]);
header('Location: index.php');
exit;
?>