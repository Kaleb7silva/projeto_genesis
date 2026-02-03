<?php
session_start();
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require 'config/db.php';

// Busca todos os membros (incluindo CPF)
$stmt = $pdo->prepare("SELECT id, nome, email, cpf, data_nascimento, status FROM usuarios WHERE perfil = 'membro' ORDER BY nome");
$stmt->execute();
$membros = $stmt->fetchAll();

// Função para gerar a senha automática
function gerarSenhaAutomatica($cpf, $data_nascimento)
{
    $dia_nascimento = date('d', strtotime($data_nascimento));
    $ultimos5_cpf = substr($cpf, -5);
    return $ultimos5_cpf . $dia_nascimento;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciar Membros - IBDESPERTAR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php require_once 'includes/nav.php'; ?>
    <main class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>👥 Gerenciar Membros</h2>
            <a href="novo_membro.php" class="btn btn-success">
                <i class="fas fa-user-plus me-1"></i> Novo Membro
            </a>
        </div>

        <?php if (empty($membros)): ?>
            <div class="alert alert-warning">Nenhum membro cadastrado.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Senha Automática</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membros as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['nome']) ?></td>
                                <td><?= htmlspecialchars($m['email']) ?></td>
                                <td>
                                    <?php if (!empty($m['cpf'])): ?>
                                        <code><?= htmlspecialchars(gerarSenhaAutomatica($m['cpf'], $m['data_nascimento'])) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">–</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $m['status'] === 'ativo' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($m['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="editar_membro.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="excluir_membro.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Excluir este membro? Todos os dados serão perdidos.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="ver_historico.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-history"></i> Histórico
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>

</html>