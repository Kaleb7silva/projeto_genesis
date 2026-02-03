<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}
require 'config/db.php';
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
$erro = $sucesso = null;

// Processar exclusão individual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_horario_id'])) {
    $horario_id = (int) $_POST['excluir_horario_id'];
    try {
        // Verifica se há agendamentos ativos associados a este horário
        $stmt_check = $pdo->prepare("SELECT COUNT(*) as total FROM agendamentos a JOIN horarios_disponiveis h ON a.data_hora = h.horario WHERE h.id = ? AND a.status != 'cancelado'");
        $stmt_check->execute([$horario_id]);
        if ($stmt_check->fetch()['total'] > 0) {
            throw new Exception("Horário com agendamentos ativos.");
        }
        $pdo->prepare("DELETE FROM horarios_disponiveis WHERE id = ?")->execute([$horario_id]);
        $sucesso = "Horário excluído!";
    } catch (Exception $e) {
        $erro = "Erro: " . $e->getMessage();
    }
}

// Processar exclusão em massa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir_massa') {
    $horarios_selecionados = json_decode($_POST['horarios_selecionados'] ?? '[]', true);
    if (!is_array($horarios_selecionados) || empty($horarios_selecionados)) {
        $erro = "Nenhum horário selecionado.";
    } else {
        try {
            $pdo->beginTransaction();
            foreach ($horarios_selecionados as $id) {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) as total FROM agendamentos a JOIN horarios_disponiveis h ON a.data_hora = h.horario WHERE h.id = ? AND a.status != 'cancelado'");
                $stmt_check->execute([$id]);
                if ($stmt_check->fetch()['total'] > 0) {
                    throw new Exception("Horário ID {$id} tem agendamentos ativos.");
                }
                $pdo->prepare("DELETE FROM horarios_disponiveis WHERE id = ?")->execute([$id]);
            }
            $pdo->commit();
            $sucesso = "Horários excluídos com sucesso!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao excluir: " . $e->getMessage();
        }
    }
}

// Carregar agendamentos
$stmt = $pdo->query("SELECT a.*, u.nome AS usuario_nome FROM agendamentos a JOIN usuarios u ON a.usuario_id = u.id ORDER BY a.data_hora DESC");
$agendamentos = $stmt->fetchAll();

// Carregar horários disponíveis — corrigido para usar 'horario' e ordenar por data + horário
$stmt = $pdo->query("SELECT * FROM horarios_disponiveis ORDER BY data_disponivel ASC, horario ASC");
$horarios_disponiveis = $stmt->fetchAll();

// Agrupar por data
$horarios_por_data = [];
foreach ($horarios_disponiveis as $h) {
    $data = $h['data_disponivel']; // já está no formato Y-m-d
    if (!isset($horarios_por_data[$data])) {
        $horarios_por_data[$data] = [];
    }
    $horarios_por_data[$data][] = $h;
}
?>

<?php require_once 'includes/nav.php'; ?>
<main class="container mt-4">
    <?php if ($alert): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($alert['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <!-- Agendamentos dos Membros -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4><i class="fas fa-users me-2"></i> Agendamentos dos Membros</h4>
        </div>
        <div class="card-body">
            <?php if (empty($agendamentos)): ?>
                <p class="text-muted">Nenhum agendamento.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Data/Hora</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agendamentos as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['usuario_nome']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($a['data_hora'])) ?></td>
                                    <td><span
                                            class="badge bg-<?= $a['status'] === 'confirmado' ? 'success' : ($a['status'] === 'pendente' ? 'warning' : 'danger') ?>"><?= ucfirst($a['status']) ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" action="admin_aprovar_agendamento.php" style="display:inline;">
                                            <input type="hidden" name="agendamento_id" value="<?= $a['id'] ?>">
                                            <select name="novo_status" onchange="this.form.submit()"
                                                class="form-select form-select-sm">
                                                <option value="">Ação</option>
                                                <option value="confirmado">✅ Confirmar</option>
                                                <option value="cancelado">❌ Cancelar</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Horários Disponíveis -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h4><i class="fas fa-clock me-2"></i> Horários Disponíveis</h4>
            <div class="btn-group">
                <button type="button" id="selecionar-todos" class="btn btn-outline-light btn-sm">✅ Selecionar Todos</button>
                <button type="button" id="desselecionar-todos" class="btn btn-outline-light btn-sm">❌ Desselecionar Todos</button>
                <button type="button" id="excluir-selecionados" class="btn btn-danger btn-sm">🗑️ Excluir Selecionados</button>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($horarios_por_data)): ?>
                <p class="text-muted">Nenhum horário cadastrado.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($horarios_por_data as $data => $horarios): ?>
                        <div class="col-md-12">
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                    <strong><?= date('d/m/Y', strtotime($data)) ?></strong>
                                    <span class="badge bg-light text-dark"><?= count($horarios) ?> horário(s)</span>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <?php foreach ($horarios as $h): ?>
                                            <div class="col-md-3 col-sm-6">
                                                <div class="card h-100">
                                                    <div class="card-body p-2">
                                                        <div class="form-check mb-2">
                                                            <input class="form-check-input checkbox-horario" type="checkbox"
                                                                value="<?= $h['id'] ?>" id="horario_<?= $h['id'] ?>"
                                                                data-status="<?= $h['status'] ?>">
                                                            <label class="form-check-label" for="horario_<?= $h['id'] ?>">
                                                                <?= date('H:i', strtotime($h['horario'])) ?>
                                                            </label>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                                            <span class="badge bg-<?= $h['status'] === 'disponivel' ? 'success' : 'danger' ?>"><?= ucfirst($h['status']) ?></span>
                                                            <form method="POST" style="display:inline;"
                                                                onsubmit="return confirm('Alterar status?');">
                                                                <input type="hidden" name="alterar_status_id" value="<?= $h['id'] ?>">
                                                                <input type="hidden" name="novo_status" value="<?= $h['status'] === 'disponivel' ? 'oculto' : 'disponivel' ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                                    <?= $h['status'] === 'disponivel' ? 'Ocultar' : 'Disponibilizar' ?>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display:inline;"
                                                                onsubmit="return confirm('Excluir?');">
                                                                <input type="hidden" name="excluir_horario_id" value="<?= $h['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                  <!--      <small class="text-muted d-block mt-2">Limite: <?= $h['limite_atendimentos'] ?> </small> -->
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Barra de exclusão em massa -->
                <div id="barra-exclusao-massa" class="alert alert-warning d-none mt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span id="contagem-selecionados">0 horários selecionados</span>
                        <button type="button" id="excluir-selecionados" class="btn btn-danger btn-sm">🗑️ Excluir Selecionados</button>
                    </div>
                </div>

                <!-- Formulário oculto para exclusão em massa -->
                <form id="form-exclusao-massa" method="POST" style="display: none;">
                    <input type="hidden" name="acao" value="excluir_massa">
                    <input type="hidden" name="horarios_selecionados" id="horarios-selecionados" value="">
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botão para Adicionar Novo Horário -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h4><i class="fas fa-plus me-2"></i> Gerenciar Horários</h4>
        </div>
        <div class="card-body">
            <p>Para adicionar novos horários, clique no botão abaixo:</p>
            <a href="admin_adicionar_horario.php" class="btn btn-primary">+ Adicionar Horários</a>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const savedScroll = sessionStorage.getItem('scrollPosition');
        if (savedScroll) {
            window.scrollTo(0, parseInt(savedScroll));
            sessionStorage.removeItem('scrollPosition');
        }
    });
    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('scrollPosition', window.scrollY);
    });

    function atualizarBarraExclusao() {
        const selecionados = Array.from(document.querySelectorAll('.checkbox-horario')).filter(cb => cb.checked);
        const total = selecionados.length;
        document.getElementById('contagem-selecionados').textContent = `${total} horário${total !== 1 ? 's' : ''} selecionado${total !== 1 ? 's' : ''}`;
        document.getElementById('barra-exclusao-massa').classList.toggle('d-none', total === 0);
    }

    document.querySelectorAll('.checkbox-horario').forEach(cb => {
        cb.addEventListener('change', atualizarBarraExclusao);
    });

    document.getElementById('selecionar-todos').addEventListener('click', function() {
        document.querySelectorAll('.checkbox-horario').forEach(cb => cb.checked = true);
        atualizarBarraExclusao();
    });

    document.getElementById('desselecionar-todos').addEventListener('click', function() {
        document.querySelectorAll('.checkbox-horario').forEach(cb => cb.checked = false);
        atualizarBarraExclusao();
    });

    document.getElementById('excluir-selecionados').addEventListener('click', function() {
        const selecionados = Array.from(document.querySelectorAll('.checkbox-horario'))
            .filter(cb => cb.checked)
            .map(cb => cb.value);

        if (selecionados.length === 0) return;

        if (!confirm(`Tem certeza que deseja excluir ${selecionados.length} horário${selecionados.length !== 1 ? 's' : ''}?`)) {
            return;
        }

        document.getElementById('horarios-selecionados').value = JSON.stringify(selecionados);
        document.getElementById('form-exclusao-massa').submit();
    });
</script>