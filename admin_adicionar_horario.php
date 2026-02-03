<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}
require 'config/db.php';
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datas_input = $_POST['data'] ?? '';
    $horarios = $_POST['horarios'] ?? [];

    if (!$datas_input || empty($horarios)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Preencha todos os campos.'];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $datas_raw = explode(',', $datas_input);
    $datas = array_filter(array_map('trim', $datas_raw));

    if (empty($datas)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Nenhuma data válida fornecida.'];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO horarios_disponiveis (dia_semana, horario, limite_atendimentos, status, data_disponivel)
            VALUES (?, ?, 1, 'disponivel', ?)
            ON DUPLICATE KEY UPDATE 
                limite_atendimentos = 1,
                status = 'disponivel'
        ");

        $dias_semana = [
            'Sunday' => 'Domingo',
            'Monday' => 'Segunda',
            'Tuesday' => 'Terça',
            'Wednesday' => 'Quarta',
            'Thursday' => 'Quinta',
            'Friday' => 'Sexta',
            'Saturday' => 'Sábado'
        ];

        foreach ($datas as $data_br) {
            $partes = explode('/', $data_br);
            if (count($partes) !== 3) {
                throw new Exception("Data inválida: {$data_br}");
            }
            $data_iso = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
            $dia_semana = $dias_semana[date('l', strtotime($data_iso))] ?? 'Desconhecido';

            foreach ($horarios as $h) {
                $horario_formatado = sprintf('%02d:00:00', $h);
                $stmt->execute([$dia_semana, $horario_formatado, $data_iso]);
            }
        }
        $pdo->commit();
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Horários adicionados com sucesso!'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erro: ' . $e->getMessage()];
    }
    header('Location: admin_horarios.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Adicionar Horários Disponíveis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/projeto_genesis/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>

<body>
    <?php require_once 'includes/nav.php'; ?>
    <main class="container mt-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4><i class="fas fa-plus me-2"></i> Adicionar Horários Disponíveis</h4>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['alert'])): ?>
                    <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show">
                        <?= $_SESSION['alert']['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['alert']); ?>
                <?php endif; ?>
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Selecione a(s) Data(s)</label>
                        <input type="text" id="datas_multiplas" name="data" class="form-control"
                            placeholder="Clique para selecionar..." required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Horários que vão estar Disponíveis:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php for ($h = 9; $h <= 19; $h++): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="horarios[]" value="<?= $h ?>"
                                        id="h<?= $h ?>">
                                    <label class="form-check-label" for="h<?= $h ?>"><?= sprintf('%02d:00', $h) ?></label>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Adicionar Horários Selecionados</button>
                    </div>
                </form>
                <a href="admin_horarios.php" class="btn btn-secondary mt-3">← Voltar</a>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#datas_multiplas", {
            mode: "multiple",
            minDate: "today",
            dateFormat: "d/m/Y",
            locale: "pt"
        });
    </script>
</body>

</html>