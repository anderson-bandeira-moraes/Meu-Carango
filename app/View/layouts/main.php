    <!DOCTYPE html>
    <html lang="pt-br" data-bs-theme="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title ?? 'Meu Carango') ?></title>
        
        <!-- Bootstrap 5.3 + Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        
        <!-- Custom CSS (opcional) -->
        <style>
            body {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            footer {
                margin-top: auto;
            }
        </style>
        <?php if (isset($extraCss)) echo $extraCss; ?>
    </head>
    <body>

        <!-- Navbar genérica -->
        <nav class="navbar navbar-expand-lg bg-body-tertiary shadow-sm">
            <div class="container-fluid">
                <a class="navbar-brand" href="/">
                    <i class="bi bi-car-front-fill me-2"></i>Meu Carango
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <!-- Links que podem ser dinâmicos via variáveis -->
                        <li class="nav-item">
                            <a class="nav-link" href="/">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/sobre">Sobre</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/contato">Contato</a>
                        </li>
                        <!-- Botão de alternar tema (modo escuro/claro) -->
                        <li class="nav-item">
                            <button id="themeToggle" class="btn btn-link nav-link" style="border: none; background: transparent;">
                                <i id="themeIcon" class="bi bi-sun-fill"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Conteúdo principal -->
        <main class="container mt-4">
            <?= $content ?? '<p class="text-muted">Nenhum conteúdo definido.</p>' ?>
        </main>

        <!-- Footer genérico -->
        <footer class="text-center py-4 mt-5 border-top">
            <div class="container">
                <p class="mb-2">&copy; <?= date('Y') ?> Meu Carango - Todos os direitos reservados.</p>
                <p class="mb-0 small">
                    <i class="bi bi-envelope"></i> contato@meucarango.com &nbsp;|&nbsp;
                    <i class="bi bi-whatsapp"></i> (11) 99999-9999
                </p>
            </div>
        </footer>

        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Script para alternar tema (modo escuro/claro) -->
        <script>
            (function() {
                const btn = document.getElementById('themeToggle');
                const icon = document.getElementById('themeIcon');
                const html = document.documentElement;
                
                // Função para atualizar ícone com base no tema atual
                function updateIcon() {
                    const theme = html.getAttribute('data-bs-theme');
                    if (theme === 'dark') {
                        icon.className = 'bi bi-moon-fill';
                    } else {
                        icon.className = 'bi bi-sun-fill';
                    }
                }
                
                // Alternar tema
                if (btn) {
                    btn.addEventListener('click', () => {
                        const currentTheme = html.getAttribute('data-bs-theme');
                        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                        html.setAttribute('data-bs-theme', newTheme);
                        localStorage.setItem('bsTheme', newTheme);
                        updateIcon();
                    });
                }
                
                // Carregar tema salvo
                const savedTheme = localStorage.getItem('bsTheme');
                if (savedTheme && (savedTheme === 'dark' || savedTheme === 'light')) {
                    html.setAttribute('data-bs-theme', savedTheme);
                    updateIcon();
                } else {
                    // Verifica preferência do sistema
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (prefersDark) {
                        html.setAttribute('data-bs-theme', 'dark');
                        updateIcon();
                    }
                }
            })();
        </script>
        
        <?php if (isset($extraJs)) echo $extraJs; ?>
    </body>
    </html>