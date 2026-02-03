<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrinho.php');
    exit;
}

$tipo = $_POST['tipo'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$quantidade = max(1, (int) ($_POST['quantidade'] ?? 1)); // Mínimo 1

$carrinho = $_SESSION['carrinho'];

if ($tipo === 'evento' && $id > 0) {
    if ($quantidade == 1) {
        // Se for 1, mantém como está
        $carrinho['eventos'][$id] = 1;
    } else {
        // Atualiza a quantidade
        $carrinho['eventos'][$id] = $quantidade;
    }
} elseif ($tipo === 'sorteio' && $id > 0) {
    if ($quantidade == 1) {
        $carrinho['sorteios'][$id] = 1;
    } else {
        $carrinho['sorteios'][$id] = $quantidade;
    }
}

salvarCarrinho($carrinho);

header('Location: carrinho.php');
exit;

function salvarCarrinho($carrinho)
{
    $_SESSION['carrinho'] = $carrinho;
    setcookie('carrinho_persistente', json_encode($carrinho), time() + 30 * 24 * 60 * 60, '/', '', false, true);
}