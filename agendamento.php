<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require 'config/db.php';
$usuario_id = $_SESSION['usuario_id'];

// Busca nome do membro para salvar no agendamento
$stmt_nome = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
$stmt_nome->execute([$usuario_id]);
$nome_membro = $stmt_nome->fetchColumn() ?: 'Membro Excluído';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['horario_id'])) {
    $horario_id = $_POST['horario_id'];
    $motivo = $_POST['motivo'] ?? '';
    $data_selecionada = $_POST['data'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM horarios_disponiveis WHERE id = ? AND status = 'disponivel'");
    $stmt->execute([$horario_id]);
    $horario = $stmt->fetch();
    if (!$horario) {
        $erro = "Horário indisponível.";
    } else {
        try {
            $pdo->beginTransaction();

            $data_hora_completa = "$data_selecionada " . $horario['horario'];

            // Verifica limite
            $stmt_check = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM agendamentos 
                WHERE DATE(data_hora) = DATE(?) AND TIME(data_hora) = TIME(?) AND status != 'cancelado'
            ");
            $stmt_check->execute([$data_hora_completa, $data_hora_completa]);
            $total = $stmt_check->fetch()['total'];

            if ($total >= $horario['limite_atendimentos']) {
                throw new Exception("Limite atingido.");
            }

            $pdo->prepare("INSERT INTO agendamentos (usuario_id, nome_membro, data_hora, motivo, status, horario_disponivel_id) 
               VALUES (?, ?, ?, ?, 'pendente', ?)")
                ->execute([$usuario_id, $nome_membro, $data_hora_completa, $motivo, $horario_id]);

            $pdo->commit();
            $sucesso = "Solicitação enviada!";
        } catch (Exception $e) {
            $pdo->rollback();
            $erro = "Erro: " . $e->getMessage();
        }
    }
}

// ✅ CORRIGIDO: ORDER BY 'horario', não 'hora_inicio'
// ✅ Também ordena por data para manter consistência
$stmt = $pdo->query("SELECT * FROM horarios_disponiveis WHERE status = 'disponivel' ORDER BY data_disponivel, horario");
$horarios_disponiveis = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE usuario_id = ? ORDER BY data_hora DESC LIMIT 5");
$stmt->execute([$usuario_id]);
$meus_agendamentos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Agendar Horário - IBDESPERTAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/projeto_genesis/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>
</head>

<body>
    <?php require_once 'includes/nav.php'; ?>
    <main class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Solicitar Agendamento</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($erro)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                        <?php endif; ?>
                        <?php if (isset($sucesso)): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Selecione a Data</label>
                                <input type="text" id="data_selecionada" class="form-control" placeholder="DD/MM/AAAA"
                                    required>
                                <input type="hidden" id="data_hidden" name="data" value="">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Selecione o Horário</label>
                                <select name="horario_id" id="horarios_disponiveis" class="form-select" required
                                    disabled>
                                    <option value="">-- Selecione a data primeiro --</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Motivo (opcional)</label>
                                <textarea name="motivo" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Solicitar</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <?php if ($_SESSION['perfil'] === 'membro'): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Meus Agendamentos</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($meus_agendamentos)): ?>
                                <p class="text-muted">Nenhum agendamento recente.</p>
                            <?php else: ?>
                                <ul class="list-group">
                                    <?php foreach ($meus_agendamentos as $a): ?>
                                        <li class="list-group-item">
                                            <strong><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></strong><br>
                                            <?= htmlspecialchars($a['motivo']) ?><br>
                                            <span
                                                class="badge bg-<?= $a['status'] === 'confirmado' ? 'success' : ($a['status'] === 'pendente' ? 'warning' : 'danger') ?>"><?= ucfirst($a['status']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Calendário Fixo 
                <div class="card">
                    <div class="card-header">
                        <h5>Calendário de Disponibilidade</h5>
                    </div>
                    <div class="card-body">
                        <div id="mini-calendario"></div>
                    </div>
                </div>-->
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#data_selecionada').on('change', function () {
                const dataISO = $('#data_hidden').val();
                if (!dataISO) return;
                $('#horarios_disponiveis').prop('disabled', true).html('<option>Carregando...</option>');
                $.post('ajax_carregar_horarios.php', {
                    data: dataISO
                }, function (res) {
                    if (res.success && res.horarios.length) {
                        let opts = '<option>-- Horário --</option>';
                        res.horarios.forEach(h => {
                            opts += `<option value="${h.id}">${h.horario}</option>`;
                        });
                        $('#horarios_disponiveis').html(opts);
                    } else {
                        $('#horarios_disponiveis').html('<option>Nenhum horário disponível</option>');
                    }
                    $('#horarios_disponiveis').prop('disabled', false);
                }).fail(function () {
                    $('#horarios_disponiveis').html('<option>Erro ao carregar horários.</option>');
                    $('#horarios_disponiveis').prop('disabled', false);
                });
            });

            flatpickr("#data_selecionada", {
                locale: "pt",
                dateFormat: "d/m/Y",
                minDate: "today",
                onChange: function (selectedDates, dateStr, instance) {
                    const partes = dateStr.split('/');
                    const dataISO = `${partes[2]}-${partes[1]}-${partes[0]}`;
                    $('#data_hidden').val(dataISO);
                    $('#data_selecionada').trigger('change');
                }
            });
        });
    </script> 