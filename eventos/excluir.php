<?php
require __DIR__ . '/../includes/nav.php';

if ($perfil !== 'admin') exit;

require __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT imagem FROM eventos WHERE id = ?");
$stmt->execute([$id]);
$imagem = $stmt->fetchColumn();

if ($imagem && strpos($imagem, 'default.jpg') === false) {
    $caminho = "../" . ltrim($imagem, '/projeto_genesis/');
    if (file_exists($caminho)) {
        unlink($caminho);
    }
}

$pdo->prepare("DELETE FROM eventos WHERE id = ?")->execute([$id]);
header('Location: /projeto_genesis/index.php');
exit;
?>