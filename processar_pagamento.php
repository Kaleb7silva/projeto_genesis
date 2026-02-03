<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'config/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Usuário não autenticado.'
    ]);
    exit;
}
$usuario_id = $_SESSION['usuario_id'];

// Verifica se há itens no carrinho
if (
    empty($_SESSION['carrinho']['eventos']) &&
    empty($_SESSION['carrinho']['sorteios'])
) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Carrinho vazio.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Calcular valor total
    $valor_total = 0;

    // Processar Eventos
    if (!empty($_SESSION['carrinho']['eventos'])) {
        foreach ($_SESSION['carrinho']['eventos'] as $evento_id => $quantidade) {
            $stmt_evento = $pdo->prepare("SELECT preco_ingresso FROM eventos WHERE id = ?");
            $stmt_evento->execute([$evento_id]);
            $preco = $stmt_evento->fetchColumn();
            $valor_total += $preco * $quantidade;
        }
    }

    // Processar Sorteios
    if (!empty($_SESSION['carrinho']['sorteios'])) {
        foreach ($_SESSION['carrinho']['sorteios'] as $sorteio_id => $quantidade) {
            $stmt_sorteio = $pdo->prepare("SELECT valor_rifa FROM sorteios WHERE id = ?");
            $stmt_sorteio->execute([$sorteio_id]);
            $preco = $stmt_sorteio->fetchColumn();
            $valor_total += $preco * $quantidade;
        }
    }

    // Insere na tabela `pagamentos` com usuario_id
    $stmt = $pdo->prepare("INSERT INTO pagamentos (usuario_id, data_pagamento, valor_total, status)
                       VALUES (?, NOW(), ?, 'aprovado')");
    $stmt->execute([$usuario_id, $valor_total]);
    $pagamento_id = $pdo->lastInsertId();
    // Insere os itens no histórico de compras
    if (!empty($_SESSION['carrinho']['eventos'])) {
        foreach ($_SESSION['carrinho']['eventos'] as $evento_id => $quantidade) {
            $stmt_evento = $pdo->prepare("SELECT titulo, preco_ingresso FROM eventos WHERE id = ?");
            $stmt_evento->execute([$evento_id]);
            $evento = $stmt_evento->fetch();
            if ($evento) {
                $total_item = $evento['preco_ingresso'] * $quantidade;
                $pdo->prepare("INSERT INTO historico_compras (usuario_id, tipo, item_id, titulo, quantidade, valor_unitario, total, data_compra)
                               VALUES (?, 'evento', ?, ?, ?, ?, ?, NOW())")
                    ->execute([$usuario_id, $evento_id, $evento['titulo'], $quantidade, $evento['preco_ingresso'], $total_item]);
            }
        }
    }

    if (!empty($_SESSION['carrinho']['sorteios'])) {
        foreach ($_SESSION['carrinho']['sorteios'] as $sorteio_id => $quantidade) {
            $stmt_sorteio = $pdo->prepare("SELECT titulo, valor_rifa FROM sorteios WHERE id = ?");
            $stmt_sorteio->execute([$sorteio_id]);
            $sorteio = $stmt_sorteio->fetch();
            if ($sorteio) {
                $total_item = $sorteio['valor_rifa'] * $quantidade;
                $pdo->prepare("INSERT INTO historico_compras (usuario_id, tipo, item_id, titulo, quantidade, valor_unitario, total, data_compra)
                               VALUES (?, 'sorteio', ?, ?, ?, ?, ?, NOW())")
                    ->execute([$usuario_id, $sorteio_id, $sorteio['titulo'], $quantidade, $sorteio['valor_rifa'], $total_item]);
            }
        }
    }

    // Limpa o carrinho
    unset($_SESSION['carrinho']);
    setcookie('carrinho_persistente', '', time() - 3600, '/', '', false, true);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Pagamento processado com sucesso!',
        'redirect' => 'meus_pedidos.php'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao registrar pagamento: ' . $e->getMessage()
    ]);
}
exit;