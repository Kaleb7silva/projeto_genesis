<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}
require 'config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$data_hora = $_POST['data_hora'] ?? '';
if (!$data_hora) {
    echo json_encode(['success' => false, 'message' => 'Data/hora inválida.']);
    exit;
}

try {
    $stmt_check = $pdo->prepare("
        SELECT * FROM horarios_disponiveis 
        WHERE DATE(hora_inicio) = DATE(?) AND TIME(hora_inicio) = TIME(?)
    ");
    $stmt_check->execute([$data_hora, $data_hora]);
    $horario = $stmt_check->fetch();

    if (!$horario) {
        throw new Exception("Horário não encontrado.");
    }

    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM agendamentos 
        WHERE DATE(data_hora) = DATE(?) AND TIME(data_hora) = TIME(?) AND status != 'cancelado'
    ");
    $stmt_count->execute([$data_hora, $data_hora]);
    $total = $stmt_count->fetch()['total'];

    if ($total >= $horario['limite_atendimentos']) {
        throw new Exception("Limite atingido.");
    }

    $pdo->prepare("INSERT INTO agendamentos (usuario_id, data_hora, motivo, status) VALUES (?, ?, '', 'pendente')")
        ->execute([$_SESSION['usuario_id'], $data_hora]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>