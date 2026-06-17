<!DOCTYPE html>
<html lang="pt-br" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Negado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .error-container {
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .error-icon {
            font-size: 5rem;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-body p-5">
                <div class="error-icon mb-4">
                    <i class="bi bi-shield-exclamation"></i>
                </div>
                <h1 class="display-4 fw-bold text-danger">403</h1>
                <h2 class="h4 mb-3">Acesso Negado</h2>
                <p class="text-muted">
                    <?= htmlspecialchars($mensagem ?? 'Você não tem permissão para acessar esta página.') ?>
                </p>
                <p class="text-muted small">
                    Se você acredita que isso é um erro, tente recarregar a página ou entre em contato com o suporte.
                </p>
                <div class="d-grid gap-2 mt-4">
                    <a href="/" class="btn btn-primary">
                        <i class="bi bi-house me-2"></i> Ir para a página inicial
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>