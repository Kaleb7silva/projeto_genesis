<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
    http_response_code(403);
    echo '<p class="text-muted">Acesso negado.</p>';
    exit;
}
require 'config/db.php';

$data = $_GET['data'] ?? '';
if (!$data || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    echo '<p class="text-muted">Data inválida.</p>';
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT h.id, h.hora_inicio, h.hora_fim, h.limite_atendimentos, h.status
        FROM horarios_disponiveis h
        WHERE h.data_disponivel = ?
        ORDER BY h.hora_inicio
    ");
    $stmt->execute([$data]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($horarios)) {
        echo '<p class="text-muted">Nenhum horário nesta data.</p>';
        exit;
    }

    $stmt2 = $pdo->prepare("SELECT TIME(a.data_hora) as hora FROM agendamentos a WHERE DATE(a.data_hora) = ? AND a.status IN ('confirmado', 'pendente')");
    $stmt2->execute([$data]);
    $agendados = array_column($stmt2->fetchAll(), 'hora');

    foreach ($horarios as $h) {
        $hora_chave = date('H:i', strtotime($h['hora_inicio']));
        $ocupado = in_array($hora_chave, $agendados);

        $bg_class = $ocupado ? 'bg-secondary' : 'bg-success';
        $text_class = 'text-white';

        echo '
        <div class="col-md-4 col-lg-3 mb-2">
            <div class="card h-100 ' . $bg_class . ' ' . $text_class . '">
                <div class="card-body p-2 text-center">
                    <div class="fw-bold">' . date('H:i', strtotime($h['hora_inicio'])) . ' – ' . date('H:i', strtotime($h['hora_fim'])) . '</div>
                    <small class="d-block">Limite: ' . $h['limite_atendimentos'] . '</small>
                    <small class="d-block mt-1">' . ($ocupado ? 'Agendado' : 'Disponível') . '</small>
                    <div class="mt-2 d-flex justify-content-center gap-1">
                        <form method="POST" style="display:inline;" onsubmit="return confirm(\'Alterar status?\');">
                            <input type="hidden" name="alterar_status_id" value="' . $h['id'] . '">
                            <input type="hidden" name="novo_status" value="' . ($h['status'] === 'disponivel' ? 'oculto' : 'disponivel') . '">
                            <button type="submit" class="btn btn-sm btn-outline-light">
                                ' . ($h['status'] === 'disponivel' ? 'Ocultar' : 'Disponibilizar') . '
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm(\'Excluir?\');">
                            <input type="hidden" name="excluir_horario_id" value="' . $h['id'] . '">
                            <button type="submit" class="btn btn-sm btn-outline-light">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>';
    }

} catch (Exception $e) {
    echo '<p class="text-danger">Erro: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>