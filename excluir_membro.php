<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require 'config/db.php';
$id = $_GET['id'] ?? 0;

// Verifica se o membro existe
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND perfil = 'membro'");
$stmt->execute([$id]);
$membro = $stmt->fetch();

if (!$membro) {
    die("Membro não encontrado.");
}

// Exclui todas as dependências primeiro (agendamentos, inscrições, pagamentos)
$pdo->beginTransaction();
try {
    // Agendamentos
    $pdo->prepare("DELETE FROM agendamentos WHERE usuario_id = ?")->execute([$id]);
    // Inscrições em eventos
    $pdo->prepare("DELETE FROM inscricoes WHERE usuario_id = ?")->execute([$id]);
    // Pagamentos
    $pdo->prepare("DELETE FROM pagamentos WHERE inscricao_id IN (SELECT id FROM inscricoes WHERE usuario_id = ?)")->execute([$id]);
    // Sorteios (se houver relação direta)
    // Se você tiver uma tabela de compras de sorteios por usuário, exclua aqui também.

    // Finalmente, exclui o usuário
    $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);

    $pdo->commit();
    header('Location: admin_membros.php?msg=excluido');
    exit;
} catch (Exception $e) {
    $pdo->rollback();
    die("Erro ao excluir: " . $e->getMessage());
}