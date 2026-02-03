<?php
session_start();
if ($_SESSION['perfil'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require 'config/db.php';
$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND perfil = 'membro'");
$stmt->execute([$id]);
$membro = $stmt->fetch();

if (!$membro) {
    die("Membro não encontrado.");
}

// Caminho da foto atual
$foto_atual = $membro['foto_perfil'] ?? 'default.png';
$caminho_foto = '/projeto_genesis/uploads/fotos/' . $foto_atual;
$fallback = '/projeto_genesis/img/utilizador-de-negocios-em-cog_78370-7040.jpg';
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $caminho_foto)) {
    $caminho_foto = $fallback;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Editar Membro - IBDESPERTAR</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Cropper.js -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Seu arquivo de estilos personalizados -->
    <link rel="stylesheet" href="/projeto_genesis/style.css?v=<?= time() ?>">
    <style>
        /* Container de notificação fixo no topo */
        #toast-notificacao-container {
            position: fixed;
            top: 70px; /* Abaixo do navbar (ajuste conforme altura do seu navbar) */
            left: 50%;
            transform: translateX(-50%);
            z-index: 1060;
            width: 100%;
            max-width: 500px;
        }
    </style>
</head>

<body>
    <?php require_once 'includes/nav.php'; ?>

    <!-- Container de Notificações Fixo -->
    <div id="toast-notificacao-container" class="p-3">
        <div id="toast-notificacao" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body d-flex align-items-center">
                <div class="flex-grow-1 me-2" id="toast-message"></div>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        </div>
    </div>

    <main class="container mt-4">
        <h2>✏️ Editar Membro</h2>

        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="text-center">
                    <img id="foto_atual_img" src="<?= $caminho_foto ?>" alt="Foto atual"
                        class="img-fluid rounded-circle">
                    <p class="mt-2"><em>Foto atual</em></p>
                </div>
            </div>
            <div class="col-md-8">
                <form id="form_editar_membro" class="row g-3">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="col-md-6">
                        <label class="form-label">Nome Completo</label>
                        <input type="text" name="nome" class="form-control"
                            value="<?= htmlspecialchars($membro['nome']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($membro['email']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gênero</label>
                        <select name="genero" class="form-select" required>
                            <option value="masculino" <?= $membro['genero'] === 'masculino' ? 'selected' : '' ?>>Masculino
                            </option>
                            <option value="feminino" <?= $membro['genero'] === 'feminino' ? 'selected' : '' ?>>Feminino
                            </option>
                            <option value="outro" <?= $membro['genero'] === 'outro' ? 'selected' : '' ?>>Outro</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Batizado?</label>
                        <select name="batizado" class="form-select" required>
                            <option value="sim" <?= $membro['batizado'] === 'sim' ? 'selected' : '' ?>>Sim</option>
                            <option value="nao" <?= $membro['batizado'] === 'nao' ? 'selected' : '' ?>>Não</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="ativo" <?= $membro['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inativo" <?= $membro['status'] === 'inativo' ? 'selected' : '' ?>>Inativo
                            </option>
                            <option value="suspenso" <?= $membro['status'] === 'suspenso' ? 'selected' : '' ?>>Suspenso
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nova Foto de Perfil (opcional)</label>
                        <input type="file" name="foto_perfil" id="foto_perfil_input" class="form-control"
                            accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-outline-secondary w-100 mb-2"
                            onclick="document.getElementById('foto_perfil_input').click()">
                            <i class="bi bi-upload me-1"></i> Escolher Imagem
                        </button>

                        <!-- Pré-visualização com crop -->
                        <div id="preview_container" class="preview-container">
                            <h6>Pré-visualização (crop quadrado):</h6>
                            <div class="border rounded p-2" style="max-width: 300px; margin: 0 auto;">
                                <img id="preview_image" src="" alt="Pré-visualização" class="img-fluid"
                                    style="max-width: 100%; display: none;">
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <button type="button" id="crop_button" class="btn btn-sm btn-success" disabled>
                                    <i class="bi bi-check-circle me-1"></i> Confirmar Crop
                                </button>
                                <button type="button" id="cancel_crop" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Cancelar
                                </button>
                            </div>
                        </div>

                        <!-- Barra de progresso -->
                        <div id="progress_container" class="progress-container">
                            <div class="progress">
                                <div id="progress_bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="progress_text" class="text-muted mt-1 d-block">0%</small>
                        </div>

                        <!-- Input oculto para armazenar a imagem cortada -->
                        <input type="hidden" name="foto_cortada" id="foto_cortada" value="">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        <a href="admin_membros.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let cropper;

        document.getElementById('foto_perfil_input').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                alert("Apenas JPG ou PNG são permitidos.");
                this.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert("A foto deve ter no máximo 5MB.");
                this.value = '';
                return;
            }
            document.getElementById('preview_container').style.display = 'block';
            document.getElementById('progress_container').style.display = 'none';
            const img = document.getElementById('preview_image');
            const url = URL.createObjectURL(file);
            img.src = url;
            img.style.display = 'block';
            if (cropper) cropper.destroy();
            cropper = new Cropper(img, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.8,
                background: false,
                zoomable: true,
                rotatable: true,
                scalable: true,
                guides: true,
                center: true,
                highlight: true,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                minContainerWidth: 300,
                minContainerHeight: 300
            });
            document.getElementById('crop_button').disabled = false;
        });

        document.getElementById('cancel_crop').addEventListener('click', function () {
            document.getElementById('preview_container').style.display = 'none';
            document.getElementById('foto_perfil_input').value = '';
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        });

        document.getElementById('crop_button').addEventListener('click', function () {
            if (!cropper) return;
            const canvas = cropper.getCroppedCanvas({ width: 300, height: 300, imageSmoothingEnabled: true, imageSmoothingQuality: 'high' });
            canvas.toBlob(function (blob) {
                const reader = new FileReader();
                reader.onloadend = function () {
                    const base64data = reader.result;
                    document.getElementById('foto_cortada').value = base64data;
                    document.getElementById('progress_container').style.display = 'block';
                    document.getElementById('progress_bar').style.width = '0%';
                    document.getElementById('progress_text').textContent = '0%';
                    simulateUpload(base64data);
                };
                reader.readAsDataURL(blob);
            }, 'image/jpeg', 0.9);
        });

        function simulateUpload(base64data) {
            let percent = 0;
            const interval = setInterval(() => {
                percent += 5;
                if (percent >= 100) {
                    clearInterval(interval);
                    document.getElementById('progress_bar').style.width = '100%';
                    document.getElementById('progress_text').textContent = '100% - Pronto!';
                    setTimeout(() => {
                        document.getElementById('progress_container').style.display = 'none';
                    }, 1000);
                } else {
                    document.getElementById('progress_bar').style.width = percent + '%';
                    document.getElementById('progress_text').textContent = percent + '%';
                }
            }, 100);
        }

        document.getElementById('form_editar_membro').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('atualizar_membro.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.foto_url) {
                            document.getElementById('foto_atual_img').src = data.foto_url + '?v=' + Date.now();
                        }
                        showToast(data.message || 'Alterações salvas com sucesso!', 'bg-success');
                    } else {
                        showToast(data.message || 'Erro ao salvar.', 'bg-danger');
                    }
                })
                .catch(error => {
                    showToast('Erro de conexão.', 'bg-danger');
                });
        });

        function showToast(message, bgClass) {
            const toastEl = document.getElementById('toast-notificacao');
            const toastBody = document.getElementById('toast-message');
            toastBody.textContent = message;
            toastEl.className = `toast ${bgClass} text-white`;
            const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }
    </script>
</body>

</html>