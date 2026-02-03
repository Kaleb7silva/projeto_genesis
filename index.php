<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /login.php');
    exit;
}
$perfil = $_SESSION['perfil'] ?? 'membro'; 
?>

<?php require_once 'includes/nav.php'; ?>

<?php
// conexão com o banco de forma segura
require_once __DIR__ . '/config/db.php';
?>

<!-- conteúdo -->
<main class="container mt-2">

    <!-- Publicações -->
    <section id="publicacoes" class="mb-4">
        <div class="bg-white rounded shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fs-4 fw-bold text-dark">Publicações</h2>
                <?php
                $stmt_total_pub = $pdo->query("SELECT COUNT(*) FROM publicacoes");
                $total_pub = $stmt_total_pub->fetchColumn();
                if ($total_pub > 3): ?>
                    <button class="btn btn-sm btn-outline-primary" onclick="mostrarTodos('publicacoes')">Ver todos</button>
                <?php endif; ?>
            </div>

            <!-- Lista (3 itens) -->
            <div id="publicacoes-resumo">
                <?php
                $stmt = $pdo->query("SELECT * FROM publicacoes ORDER BY data_publicacao DESC LIMIT 3");
                $publicacoes = $stmt->fetchAll();
                if ($publicacoes): ?>
                    <div class="row g-4">
                        <?php foreach ($publicacoes as $p): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 d-flex flex-column">
                                    <!-- Parte Verde: Título e Descrição -->
                                    <div class="card-header bg-light border-0 pt-3 pb-2">
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($p['titulo']) ?></h5>
                                        <p class="card-text text-muted small mb-0">
                                            <?= substr(htmlspecialchars($p['conteudo']), 0, 100) ?>
                                        </p>
                                    </div>
                                    <!-- Parte Vermelha: Conteúdo Dinâmico (Imagem ou Vídeo) -->
                                    <div class="card-body d-flex flex-grow-1 position-relative p-0">
                                        <?php if ($p['tipo'] === 'imagem' && !empty($p['arquivo'])): ?>
                                            <!-- Imagem: exibida diretamente -->
                                            <img src="<?= htmlspecialchars($p['arquivo']) ?>"
                                                alt="<?= htmlspecialchars($p['titulo']) ?>"
                                                class="w-100 h-100"
                                                style="object-fit: cover; object-position: center; aspect-ratio: 16/9;">
                                        <?php elseif ($p['tipo'] === 'pdf'): ?>
                                            <!-- PDF: botão "Ver PDF" -->
                                            <div class="d-flex align-items-center justify-content-center w-100 h-100">
                                                <a href="<?= htmlspecialchars($p['arquivo']) ?>" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-file-pdf me-1"></i> Ver PDF
                                                </a>
                                            </div>
                                        <?php elseif ($p['tipo'] === 'video'): ?>
                                            <!-- Vídeo: reprodução automática -->
                                            <?php
                                            // Detecta se é um link do YouTube
                                            $is_youtube = strpos($p['arquivo'], 'youtube.com') !== false || strpos($p['arquivo'], 'youtu.be') !== false;
                                            ?>
                                            <?php if ($is_youtube): ?>
                                                <!-- YouTube: iframe -->
                                                <div class="position-absolute top-0 start-0 w-100 h-100">
                                                    <?php
                                                    // Extrai o ID do vídeo do YouTube
                                                    $video_id = '';
                                                    if (strpos($p['arquivo'], 'v=') !== false) {
                                                        $video_id = explode('v=', $p['arquivo'])[1];
                                                        $video_id = explode('&', $video_id)[0];
                                                    } elseif (strpos($p['arquivo'], 'youtu.be/') !== false) {
                                                        $video_id = explode('youtu.be/', $p['arquivo'])[1];
                                                        $video_id = explode('?', $video_id)[0];
                                                    }
                                                    ?>
                                                    <?php if ($video_id): ?>
                                                        <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($video_id) ?>?autoplay=1&mute=1&loop=1&controls=0"
                                                            title="Vídeo do YouTube"
                                                            allowfullscreen
                                                            class="w-100 h-100"
                                                            style="border: none; aspect-ratio: 16/9;"></iframe>
                                                    <?php else: ?>
                                                        <p class="text-muted">ID do vídeo inválido.</p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <!-- Vídeo local (MP4, WebM, MOV): tag <video> com autoplay -->
                                                <video controls autoplay muted loop class="w-100 h-100" style="object-fit: cover; aspect-ratio: 16/9;">
                                                    <source src="<?= htmlspecialchars($p['arquivo']) ?>"
                                                        type="video/<?= pathinfo($p['arquivo'], PATHINFO_EXTENSION) ?>">
                                                    Seu navegador não suporta vídeo.
                                                </video>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Nenhuma publicação disponível no momento.</p>
                <?php endif; ?>
            </div>

            <!-- Lista completa (todos) - oculta por padrão -->
            <div id="publicacoes-completo" class="mt-3" style="display: none;">
                <?php
                $stmt_todos = $pdo->query("SELECT * FROM publicacoes ORDER BY data_publicacao DESC");
                $todas_publicacoes = $stmt_todos->fetchAll();
                if ($todas_publicacoes): ?>
                    <div class="row g-4">
                        <?php foreach ($todas_publicacoes as $p): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 d-flex flex-column">
                                    <!-- Parte Verde: Título e Descrição -->
                                    <div class="card-header bg-light border-0 pt-3 pb-2">
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($p['titulo']) ?></h5>
                                        <p class="card-text text-muted small mb-0">
                                            <?= substr(htmlspecialchars($p['conteudo']), 0, 100) ?>
                                        </p>
                                    </div>
                                    <!-- Parte Vermelha: Conteúdo Dinâmico (Imagem ou Vídeo) -->
                                    <div class="card-body d-flex flex-grow-1 position-relative p-0">
                                        <?php if ($p['tipo'] === 'imagem' && !empty($p['arquivo'])): ?>
                                            <!-- Imagem: exibida diretamente -->
                                            <img src="<?= htmlspecialchars($p['arquivo']) ?>"
                                                alt="<?= htmlspecialchars($p['titulo']) ?>"
                                                class="w-100 h-100"
                                                style="object-fit: cover; object-position: center; aspect-ratio: 16/9;">
                                        <?php elseif ($p['tipo'] === 'pdf'): ?>
                                            <!-- PDF: botão "Ver PDF" -->
                                            <div class="d-flex align-items-center justify-content-center w-100 h-100">
                                                <a href="<?= htmlspecialchars($p['arquivo']) ?>" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-file-pdf me-1"></i> Ver PDF
                                                </a>
                                            </div>
                                        <?php elseif ($p['tipo'] === 'video'): ?>
                                            <!-- Vídeo: reprodução automática -->
                                            <?php
                                            // Detecta se é um link do YouTube
                                            $is_youtube = strpos($p['arquivo'], 'youtube.com') !== false || strpos($p['arquivo'], 'youtu.be') !== false;
                                            ?>
                                            <?php if ($is_youtube): ?>
                                                <!-- YouTube: iframe -->
                                                <div class="position-absolute top-0 start-0 w-100 h-100">
                                                    <?php
                                                    // Extrai o ID do vídeo do YouTube
                                                    $video_id = '';
                                                    if (strpos($p['arquivo'], 'v=') !== false) {
                                                        $video_id = explode('v=', $p['arquivo'])[1];
                                                        $video_id = explode('&', $video_id)[0]; // Remove parâmetros extras
                                                    } elseif (strpos($p['arquivo'], 'youtu.be/') !== false) {
                                                        $video_id = explode('youtu.be/', $p['arquivo'])[1];
                                                        $video_id = explode('?', $video_id)[0]; // Remove parâmetros extras
                                                    }
                                                    ?>
                                                    <?php if ($video_id): ?>
                                                        <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($video_id) ?>?autoplay=1&mute=1&loop=1&controls=0"
                                                            title="Vídeo do YouTube"
                                                            allowfullscreen
                                                            class="w-100 h-100"
                                                            style="border: none; aspect-ratio: 16/9;"></iframe>
                                                    <?php else: ?>
                                                        <p class="text-muted">ID do vídeo inválido.</p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <!-- Vídeo local (MP4, WebM, MOV): tag <video> com autoplay -->
                                                <video controls autoplay muted loop class="w-100 h-100" style="object-fit: cover; aspect-ratio: 16/9;">
                                                    <source src="<?= htmlspecialchars($p['arquivo']) ?>"
                                                        type="video/<?= pathinfo($p['arquivo'], PATHINFO_EXTENSION) ?>">
                                                    Seu navegador não suporta vídeo.
                                                </video>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="ocultarTodos('publicacoes')">Mostrar menos</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Scripts para alternar visibilidade -->
    <script>
        function mostrarTodos(secao) {
            document.getElementById(`${secao}-resumo`).style.display = 'none';
            document.getElementById(`${secao}-completo`).style.display = 'block';
        }

        function ocultarTodos(secao) {
            document.getElementById(`${secao}-resumo`).style.display = 'block';
            document.getElementById(`${secao}-completo`).style.display = 'none';
        }
    </script>

    <!-- Sorteios -->
    <section id="sorteios" class="mb-4">
        <div class="bg-white rounded shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fs-4 fw-bold text-dark">Sorteios</h2>
                <?php
                $stmt_total_sorteios = $pdo->query("SELECT COUNT(*) FROM sorteios WHERE publicado = 1");
                $total_sorteios = $stmt_total_sorteios->fetchColumn();
                if ($total_sorteios > 3): ?>
                    <button class="btn btn-sm btn-outline-purple" onclick="mostrarTodos('sorteios')">Ver todos</button>
                <?php endif; ?>
            </div>

            <!-- Lista resumida (3 itens) -->
            <div id="sorteios-resumo">
                <?php
                $stmt = $pdo->query("SELECT * FROM sorteios WHERE publicado = 1 ORDER BY data_sorteio DESC LIMIT 3");
                $sorteios = $stmt->fetchAll();
                if ($sorteios): ?>
                    <div class="row g-4">
                        <?php foreach ($sorteios as $s): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 d-flex flex-column">
                                    <!-- Imagem em destaque no topo -->
                                    <div class="position-relative">
                                        <img src="<?= htmlspecialchars($s['imagem'] ?: '/projeto_genesis/img/default.jpg') ?>"
                                            alt="<?= htmlspecialchars($s['titulo']) ?>" class="card-img-top"
                                            style="height: 180px; object-fit: cover; width: 100%;">
                                    </div>
                                    <!-- Corpo do card -->
                                    <div class="card-body d-flex flex-column flex-grow-1">
                                        <h5 class="card-title fw-bold"><?= htmlspecialchars($s['titulo']) ?></h5>
                                        <p class="card-text text-muted small">
                                            <?= substr(htmlspecialchars($s['descricao']), 0, 100) ?>
                                        </p>
                                        <p class="card-text"><small class="text-muted"><strong>Data:</strong>
                                                <?= date('d/m/Y', strtotime($s['data_sorteio'])) ?></small></p>
                                        <p class="card-text"><small class="text-success fw-bold"><strong>Valor da
                                                    rifa:</strong>
                                                R$ <?= number_format($s['valor_rifa'], 2, ',', '.') ?></small></p>
                                    </div>
                                    <!-- Footer com botão -->
                                    <div class="card-footer bg-white border-0 pt-0">
                                        <form method="POST" action="carrinho.php" class="mt-2">
                                            <input type="hidden" name="tipo" value="sorteio">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit"
                                                class="btn btn-sm btn-outline-purple w-100">Participar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Nenhum sorteio disponível no momento.</p>
                <?php endif; ?>
            </div>

            <!-- Lista completa (todos os publicados) - oculta por padrão -->
            <div id="sorteios-completo" class="mt-3" style="display: none;">
                <?php
                $stmt_todos = $pdo->query("SELECT * FROM sorteios WHERE publicado = 1 ORDER BY data_sorteio DESC");
                $todos_sorteios = $stmt_todos->fetchAll();
                if ($todos_sorteios): ?>
                    <div class="row g-4">
                        <?php foreach ($todos_sorteios as $s): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 d-flex flex-column">
                                    <div class="position-relative">
                                        <img src="<?= htmlspecialchars($s['imagem'] ?: '/projeto_genesis/img/default.jpg') ?>"
                                            alt="<?= htmlspecialchars($s['titulo']) ?>" class="card-img-top"
                                            style="height: 180px; object-fit: cover; width: 100%;">
                                    </div>
                                    <div class="card-body d-flex flex-column flex-grow-1">
                                        <h5 class="card-title fw-bold"><?= htmlspecialchars($s['titulo']) ?></h5>
                                        <p class="card-text text-muted small">
                                            <?= substr(htmlspecialchars($s['descricao']), 0, 100) ?>
                                        </p>
                                        <p class="card-text"><small class="text-muted"><strong>Data:</strong>
                                                <?= date('d/m/Y', strtotime($s['data_sorteio'])) ?></small></p>
                                        <p class="card-text"><small class="text-success fw-bold"><strong>Valor da
                                                    rifa:</strong>
                                                R$ <?= number_format($s['valor_rifa'], 2, ',', '.') ?></small></p>
                                    </div>
                                    <div class="card-footer bg-white border-0 pt-0">
                                        <form method="POST" action="carrinho.php" class="mt-2">
                                            <input type="hidden" name="tipo" value="sorteio">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit"
                                                class="btn btn-sm btn-outline-purple w-100">Participar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="ocultarTodos('sorteios')">Mostrar
                            menos</button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Resultados Recentes -->
            <div class="mt-4">
                <h4>🏆 Resultados Recentes</h4>
                <?php
                $stmt_resultados = $pdo->prepare("
                SELECT s.*, u.nome AS vencedor_nome 
                FROM sorteios s 
                LEFT JOIN usuarios u ON s.vencedor_membro_id = u.id 
                WHERE s.resultado_exibido = 1 
                ORDER BY s.data_sorteio DESC
                LIMIT 3
            ");
                $stmt_resultados->execute();
                $resultados = $stmt_resultados->fetchAll();

                if (empty($resultados)): ?>
                    <p class="text-muted">Nenhum sorteio realizado.</p>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($resultados as $r): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 border-success">
                                    <div class="card-body">
                                        <h5 class="card-title fw-bold text-success"><?= htmlspecialchars($r['titulo']) ?>
                                        </h5>
                                        <p class="card-text text-muted small">
                                            <?= substr(htmlspecialchars($r['descricao']), 0, 60) ?>
                                        </p>
                                        <p class="card-text"><small class="text-muted">Data:
                                                <?= date('d/m/Y', strtotime($r['data_sorteio'])) ?></small></p>
                                        <?php if ($r['vencedor_membro_id']): ?>
                                            <p class="card-text"><strong>Vencedor:</strong>
                                                <?= htmlspecialchars($r['vencedor_nome']) ?></p>
                                        <?php elseif ((int) $r['numero_vencedor'] > 0): ?>
                                            <p class="card-text"><strong>Número Vencedor:</strong>
                                                <?= (int) $r['numero_vencedor'] ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Eventos -->
    <section id="eventos" class="mb-4">
        <div class="bg-white rounded shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fs-4 fw-bold text-dark">Eventos</h2>
                <?php
                $stmt_total_eventos = $pdo->query("SELECT COUNT(*) FROM eventos WHERE publicado = 1");
                $total_eventos = $stmt_total_eventos->fetchColumn();
                if ($total_eventos > 3): ?>
                    <button class="btn btn-sm btn-outline-success" onclick="mostrarTodos('eventos')">Ver todos</button>
                <?php endif; ?>
            </div>

            <!-- Lista resumida (3 itens) -->
            <div id="eventos-resumo">
                <?php
                $stmt = $pdo->query("SELECT * FROM eventos WHERE publicado = 1 ORDER BY data_evento DESC LIMIT 3");
                $eventos = $stmt->fetchAll();
                if ($eventos): ?>
                    <div class="row g-4">
                        <?php foreach ($eventos as $e): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 d-flex flex-column">
                                    <!-- Imagem em destaque no topo -->
                                    <div class="position-relative">
                                        <img src="<?= htmlspecialchars($e['imagem'] ?: '/projeto_genesis/img/default.jpg') ?>"
                                            alt="<?= htmlspecialchars($e['titulo']) ?>" class="card-img-top"
                                            style="height: 180px; object-fit: cover; width: 100%;">
                                    </div>
                                    <!-- Corpo do card -->
                                    <div class="card-body d-flex flex-column flex-grow-1">
                                        <h5 class="card-title fw-bold"><?= htmlspecialchars($e['titulo']) ?></h5>
                                        <p class="card-text text-muted small">
                                            <?= substr(htmlspecialchars($e['descricao']), 0, 100) ?>
                                        </p>
                                        <p class="card-text"><small class="text-muted"><strong>Data:</strong>
                                                <?= date('d/m/Y H:i', strtotime($e['data_evento'])) ?></small></p>
                                        <p class="card-text"><small class="text-muted"><strong>Local:</strong>
                                                <?= htmlspecialchars($e['local']) ?></small></p>
                                        <p class="card-text"><small class="text-success fw-bold"><strong>Preço do
                                                    ingresso:</strong>
                                                R$ <?= number_format($e['preco_ingresso'], 2, ',', '.') ?></small></p>
                                    </div>
                                    <!-- Footer com botão -->
                                    <div class="card-footer bg-white border-0 pt-0">
                                        <form method="POST" action="carrinho.php" class="mt-2">
                                            <input type="hidden" name="tipo" value="evento">
                                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success w-100">Adicionar ao
                                                Carrinho</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Nenhum evento publicado no momento.</p>
                <?php endif; ?>
            </div>

            <!-- Lista completa (todos os publicados) - oculta por padrão -->
            <div id="eventos-completo" class="mt-3" style="display: none;">
                <?php
                $stmt_todos = $pdo->query("SELECT * FROM eventos WHERE publicado = 1 ORDER BY data_evento DESC");
                $todos_eventos = $stmt_todos->fetchAll();
                if ($todos_eventos): ?>
                    <div class="row g-4">
                        <?php foreach ($todos_eventos as $e): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 d-flex flex-column">
                                    <div class="position-relative">
                                        <img src="<?= htmlspecialchars($e['imagem'] ?: '/projeto_genesis/img/default.jpg') ?>"
                                            alt="<?= htmlspecialchars($e['titulo']) ?>" class="card-img-top"
                                            style="height: 180px; object-fit: cover; width: 100%;">
                                    </div>
                                    <div class="card-body d-flex flex-column flex-grow-1">
                                        <h5 class="card-title fw-bold"><?= htmlspecialchars($e['titulo']) ?></h5>
                                        <p class="card-text text-muted small">
                                            <?= substr(htmlspecialchars($e['descricao']), 0, 100) ?>
                                        </p>
                                        <p class="card-text"><small class="text-muted"><strong>Data:</strong>
                                                <?= date('d/m/Y H:i', strtotime($e['data_evento'])) ?></small></p>
                                        <p class="card-text"><small class="text-muted"><strong>Local:</strong>
                                                <?= htmlspecialchars($e['local']) ?></small></p>
                                        <p class="card-text"><small class="text-success fw-bold"><strong>Preço do
                                                    ingresso:</strong>
                                                R$ <?= number_format($e['preco_ingresso'], 2, ',', '.') ?></small></p>
                                    </div>
                                    <div class="card-footer bg-white border-0 pt-0">
                                        <form method="POST" action="carrinho.php" class="mt-2">
                                            <input type="hidden" name="tipo" value="evento">
                                            <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success w-100">Adicionar ao
                                                Carrinho</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3 text-end">
                        <button class="btn btn-sm btn-outline-secondary" onclick="ocultarTodos('eventos')">Mostrar
                            menos</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Scripts para alternar visibilidade -->
    <script>
        function mostrarTodos(secao) {
            document.getElementById(`${secao}-resumo`).style.display = 'none';
            document.getElementById(`${secao}-completo`).style.display = 'block';
        }

        function ocultarTodos(secao) {
            document.getElementById(`${secao}-resumo`).style.display = 'block';
            document.getElementById(`${secao}-completo`).style.display = 'none';
        }
    </script>
</main> 

<?php include __DIR__ . '../includes/footer.php'; ?>