<?php
session_start();

// Verifica se é admin
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Acesso negado. Apenas administradores podem cadastrar membros.'];
    header('Location: admin_membros.php');
    exit;
}

require 'config/db.php';

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
$data_nascimento_br = $_POST['data_nascimento'] ?? ''; // formato DD/MM/YYYY
$genero = $_POST['genero'] ?? 'outro';
$batizado = $_POST['batizado'] ?? 'nao';
$status = $_POST['status'] ?? 'ativo';
$foto_perfil = 'default.png';

$erro = null;

// Validações
if (strlen($cpf) !== 11) {
    $erro = "CPF inválido. Deve ter 11 dígitos.";
}

// Valida e converte data de DD/MM/YYYY → Y-m-d
$data_nascimento = null;
if ($data_nascimento_br) {
    $partes = explode('/', $data_nascimento_br);
    if (count($partes) === 3) {
        $dia = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
        $mes = str_pad($partes[1], 2, '0', STR_PAD_LEFT);
        $ano = $partes[2];
        $data_iso = "$ano-$mes-$dia";
        // Verifica se é uma data válida
        if (checkdate((int) $mes, (int) $dia, (int) $ano)) {
            $data_nascimento = $data_iso;
        } else {
            $erro = "Data de nascimento inválida.";
        }
    } else {
        $erro = "Formato de data inválido. Use DD/MM/AAAA.";
    }
} else {
    $erro = "Data de nascimento é obrigatória.";
}

if (!$nome || !$email || !$cpf || !$data_nascimento) {
    $erro = $erro ?: "Preencha todos os campos obrigatórios.";
}

if ($erro) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => $erro];
    $_SESSION['form_data'] = $_POST; // opcional: repopular formulário
    header('Location: novo_membro.php');
    exit;
}

// Gera senha automática: últimos 5 do CPF + dia do nascimento (2 dígitos)
$dia_nascimento = date('d', strtotime($data_nascimento));
$ultimos5_cpf = substr($cpf, -5);
$senha_automatica = $ultimos5_cpf . $dia_nascimento;
$hash_senha = password_hash($senha_automatica, PASSWORD_DEFAULT);

// Upload de foto (opcional)
if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
    $arquivo = $_FILES['foto_perfil'];
    $tipo = $arquivo['type'];
    $tamanho = $arquivo['size'];

    if (!in_array($tipo, ['image/jpeg', 'image/png', 'image/jpg'])) {
        $erro = "Apenas JPG ou PNG são permitidos.";
    } elseif ($tamanho > 5 * 1024 * 1024) {
        $erro = "A foto deve ter no máximo 5MB.";
    } else {
        $upload_dir = __DIR__ . '/uploads/fotos/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0755, true);

        $ext = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $nome_arquivo = uniqid('perfil_', true) . '.' . strtolower($ext);
        $caminho_destino = $upload_dir . $nome_arquivo;

        if (redimensionarImagem($arquivo['tmp_name'], $caminho_destino, 300, 300, $tipo)) {
            $foto_perfil = $nome_arquivo;
        } else {
            $erro = "Erro ao processar a imagem.";
        }
    }
}

if ($erro) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => $erro];
    header('Location: novo_membro.php');
    exit;
}

// Função para redimensionar imagem (definida dentro do escopo)
function redimensionarImagem($origem, $destino, $largura_max, $altura_max, $tipo)
{
    list($largura, $altura) = getimagesize($origem);
    if (!$largura || !$altura)
        return false;

    $ratio = min($largura_max / $largura, $altura_max / $altura);
    $nova_largura = (int) ($largura * $ratio);
    $nova_altura = (int) ($altura * $ratio);

    $imagem_destino = imagecreatetruecolor($nova_largura, $nova_altura);
    if (!$imagem_destino)
        return false;

    if ($tipo === 'image/jpeg' || $tipo === 'image/jpg') {
        $imagem_origem = imagecreatefromjpeg($origem);
    } elseif ($tipo === 'image/png') {
        $imagem_origem = imagecreatefrompng($origem);
        imagealphablending($imagem_destino, false);
        imagesavealpha($imagem_destino, true);
    } else {
        return false;
    }

    if (!$imagem_origem)
        return false;
    imagecopyresampled($imagem_destino, $imagem_origem, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura, $altura);

    $salvo = ($tipo === 'image/jpeg' || $tipo === 'image/jpg')
        ? imagejpeg($imagem_destino, $destino, 90)
        : imagepng($imagem_destino, $destino, 6);

    imagedestroy($imagem_origem);
    imagedestroy($imagem_destino);
    return $salvo;
}

// Inserir no banco
try {
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nome, email, cpf, senha, perfil, genero, data_nascimento, batizado, status, foto_perfil) 
        VALUES (?, ?, ?, ?, 'membro', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $nome,
        $email,
        $cpf,
        $hash_senha,
        $genero,
        $data_nascimento,
        $batizado,
        $status,
        $foto_perfil
    ]);

    // Exibe sucesso na própria página
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <title>Cadastro Concluído - IBDESPERTAR</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>

    <body>
        <?php require_once 'includes/nav.php'; ?>
        <main class="container mt-4">
            <div class="alert alert-success">
                <h4>✅ Membro cadastrado com sucesso!</h4>
                <p><strong>Nome:</strong> <?= htmlspecialchars($nome) ?></p>
                <p><strong>Email (login):</strong> <?= htmlspecialchars($email) ?></p>
                <p><strong>Senha automática:</strong> <code><?= htmlspecialchars($senha_automatica) ?></code></p>
                <p class="text-warning">⚠️ Informe essa senha ao membro. Ele poderá alterá-la depois.</p>
                <a href="admin_membros.php" class="btn btn-secondary mt-3">Voltar para lista</a>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </body>

    </html>
    <?php
    exit;

} catch (PDOException $e) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erro ao cadastrar: ' . $e->getMessage()];
    header('Location: novo_membro.php');
    exit;
}
?>