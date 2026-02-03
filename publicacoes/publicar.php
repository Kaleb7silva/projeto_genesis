<?php
session_start();
if (!isset($_SESSION['usuario_id']) || ($_SESSION['perfil'] ?? '') !== 'admin') {
    header('Location: /projeto_genesis/login.php');
    exit;
}

require __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$acao = $_GET['acao'] ?? '';

if ($id > 0 && in_array($acao, ['publicar', 'despublicar'])) {
    $valor = ($acao === 'publicar') ? 1 : 0;
    $pdo->prepare("UPDATE publicacoes SET publicado = ? WHERE id = ?")->execute([$valor, $id]);
}

header('Location: index.php');
exit;