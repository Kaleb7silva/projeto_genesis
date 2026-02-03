<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}
require 'config/db.php';
header('Content-Type: application/json');

try {
    // Datas com horários disponíveis
    $stmt1 = $pdo->query("
        SELECT DISTINCT DATE(data_disponivel) AS data_disponivel
        FROM horarios_disponiveis
        WHERE status = 'disponivel'
    ");
    $disponiveis = array_column($stmt1->fetchAll(PDO::FETCH_ASSOC), 'data_disponivel');

    // Datas com agendamentos pendentes ou confirmados
    $stmt2 = $pdo->query("
        SELECT DISTINCT DATE(data_hora) AS data
        FROM agendamentos
        WHERE status IN ('pendente', 'confirmado')
    ");
    $agendados = array_column($stmt2->fetchAll(PDO::FETCH_ASSOC), 'data');

    echo json_encode([
        'disponiveis' => $disponiveis,
        'agendados' => $agendados
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>