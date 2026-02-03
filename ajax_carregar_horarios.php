<?php
session_start();
require 'config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$data = $_POST['data'] ?? '';
if (!$data || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo json_encode(['success' => false, 'message' => 'Data inválida.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, horario
        FROM horarios_disponiveis
        WHERE data_disponivel = ? AND status = 'disponivel'
        ORDER BY horario
    ");
    $stmt->execute([$data]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $horarios_livres = [];
    foreach ($horarios as $h) {
        $stmt_check = $pdo->prepare("
            SELECT id FROM agendamentos 
            WHERE DATE(data_hora) = DATE(?) AND TIME(data_hora) = TIME(?) AND status != 'cancelado'
            LIMIT 1
        ");
        $stmt_check->execute([$data, $h['horario']]);
        if (!$stmt_check->fetch()) {
            $horarios_livres[] = $h;
        }
    }

    echo json_encode(['success' => true, 'horarios' => $horarios_livres]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}