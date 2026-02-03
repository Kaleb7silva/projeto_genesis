<?php
function gerarBotaoHorario($pdo, $data, $hora_inicio, $limite, $horario_disponivel_id, $perfil) {
    // Formata o horário exato (ex: 10:00:00)
    $hora_exata = $hora_inicio; // já no formato H:i:s

    // Verifica se já existe agendamento nesse horário
    $stmt = $pdo->prepare("
        SELECT * FROM agendamentos 
        WHERE DATE(data_hora) = ? AND TIME(data_hora) = ? 
        ORDER BY status DESC
        LIMIT 1
    ");
    $stmt->execute([$data, $hora_exata]);
    $agendamento = $stmt->fetch();

    $agendamento_id = $agendamento ? $agendamento['id'] : null;
    $status_atual = $agendamento ? $agendamento['status'] : 'disponivel';

    // Determina o ícone e classe
    if ($status_atual === 'confirmado') {
        $icone = '<i class="fas fa-check-circle text-success"></i>'; // ✅ verde
    } elseif ($status_atual === 'pendente') {
        $icone = '<i class="fas fa-clock text-warning"></i>'; // ⏳ amarelo
    } elseif ($status_atual === 'cancelado') {
        $icone = '<i class="fas fa-times-circle text-muted"></i>'; // ❌ cinza
        $status_atual = 'disponivel'; // tratamos como disponível
    } else {
        $icone = '<i class="fas fa-check-circle text-muted"></i>'; // ⚪ cinza
    }

    // Botão
    $btn = "<a href='#' class='btn btn-outline-secondary btn-sm d-flex align-items-center gap-2 dropdown-item' 
              data-data='$data' 
              data-hora='$hora_exata' 
              data-status='$status_atual'";

    if ($agendamento_id) {
        $btn .= " data-agendamento-id='$agendamento_id'";
    }

    $btn .= ">$icone " . substr($hora_exata, 0, 5) . "</a>";

    return $btn;
}