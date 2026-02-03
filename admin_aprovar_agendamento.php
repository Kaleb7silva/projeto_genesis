<?php
session_start();

// Função para detectar requisição AJAX
function isAjax()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Verificação de autenticação
if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'admin') {
    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
        exit;
    } else {
        header('Location: index.php');
        exit;
    }
}

require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Método inválido.']);
        exit;
    } else {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Método inválido.'];
        header('Location: admin_horarios.php');
        exit;
    }
}

$agendamento_id = $_POST['agendamento_id'] ?? null;
$novo_status = $_POST['novo_status'] ?? null;

if (!$agendamento_id || !in_array($novo_status, ['confirmado', 'cancelado'])) {
    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
        exit;
    } else {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Dados inválidos.'];
        header('Location: admin_horarios.php');
        exit;
    }
}

try {
    $stmt = $pdo->prepare("UPDATE agendamentos SET status = ? WHERE id = ?");
    $stmt->execute([$novo_status, $agendamento_id]);

    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } else {
        $_SESSION['alert'] = $stmt->rowCount() > 0
            ? ['type' => 'success', 'message' => 'Status atualizado com sucesso!']
            : ['type' => 'warning', 'message' => 'Nenhuma alteração realizada.'];
        header('Location: admin_horarios.php');
        exit;
    }
} catch (Exception $e) {
    if (isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        exit;
    } else {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erro: ' . $e->getMessage()];
        header('Location: admin_horarios.php');
        exit;
    }
}
?>