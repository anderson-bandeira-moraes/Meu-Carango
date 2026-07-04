<?php
declare(strict_types=1);

// ============== CONSTANTES DE CAMINHO ==============
define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', ROOT_DIR . '/app');
define('VIEW_DIR', APP_DIR . '/View');
define('CONFIG_DIR', ROOT_DIR . '/config');

// ============== AUTOLOAD ==============
require ROOT_DIR . '/vendor/autoload.php';

// ============== AMBIENTE (.env) ==============
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(ROOT_DIR);
$dotenv->safeLoad();

// ============== CONFIGURAÇÃO DE ERROS ==============
$env = $_ENV['APP_ENV'] ?? 'production';
if ($env === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ============== SESSÃO SEGURA ==============
use App\Core\Session;

Session::start([
    'cookie_lifetime' => 0,      // até fechar navegador
    'cookie_secure'   => ($_ENV['APP_ENV'] === 'production'),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

// ============== CARREGAR HELPERS ==============
require_once ROOT_DIR . '/app/Helpers/csrf.php';
require_once ROOT_DIR . '/app/Helpers/veiculos.php';

// ============== CONEXÃO COM BANCO ==============
use App\Core\Database;

Database::init([
    'host'    => $_ENV['DB_HOST'],
    'name'    => $_ENV['DB_NAME'],
    'user'    => $_ENV['DB_USER'],
    'pass'    => $_ENV['DB_PASS'],
    'charset' => 'utf8mb4',
]);

// ============== CONTAINER DE DEPENDÊNCIAS (SIMPLES) ==============
use App\Core\Container;
use App\Core\Contracts\SessionInterface;
use App\Core\SessionWrapper;
use App\Core\Contracts\CsrfTokenGeneratorInterface;
use App\Core\Security\CsrfTokenGenerator;
use App\Middleware\CsrfTokenMiddleware;
use App\Middleware\CsrfValidationMiddleware;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;
use App\Repository\TwoFactorRepository;
use App\Service\MailService;
use App\Service\TwoFactorService;
use App\Middleware\TwoFactorMiddleware;
use App\Controller\TwoFactorController;
use App\Middleware\UserTwoFactorMiddleware;
use App\Service\UserTwoFactorService;
use App\Controller\UserTwoFactorController;
use App\Repository\UsuarioRepository;
// --- NOVOS USES PARA VEÍCULOS ---
use App\Repository\VeiculoRepository;
use App\Repository\VeiculoCombustaoRepository;
use App\Repository\VeiculoEletricoRepository;
use App\Repository\VeiculoHibridoRepository;
use App\Repository\VeiculoGNVRepository;
use App\Repository\VeiculoOpcionalRepository;
use App\Repository\OpcionalRepository;
use App\Repository\VeiculoImagemRepository;
use App\Repository\MarcaRepository;
use App\Repository\ModeloRepository;
use App\Service\VeiculoService;
// --- NOVOS USES PARA FORMREQUEST ---
use App\Core\Request;
use App\Requests\LoginRequest;
use App\Requests\TwoFactorRequest;
use App\Requests\VeiculoRequest;
use App\Requests\VeiculoCombustaoRequest;
use App\Requests\VeiculoEletricoRequest;
use App\Requests\VeiculoHibridoRequest;
use App\Requests\VeiculoGNVRequest;
use App\Requests\VeiculoOpcionalRequest;
use App\Requests\VeiculoImagemRequest;
// --- NOVOS USES PARA CONTROLLER ---
use App\Controller\VeiculoController;

$container = new Container();

// Carrega a configuração de logging
$logConfig = require CONFIG_DIR . '/logging.php';

// Registra o Logger no container (agora apenas com a interface PSR-3)
$loggerFactory = function() use ($logConfig) {
    $logger = new \Monolog\Logger($logConfig['channel']);

    // Handler com rotação diária
    $handler = new RotatingFileHandler(
        $logConfig['path'],
        $logConfig['days'],
        $logConfig['level']
    );
    $logger->pushHandler($handler);

    // Adiciona os processadores configurados
    foreach ($logConfig['processors'] as $processorClass) {
        $logger->pushProcessor(new $processorClass());
    }

    return $logger;
};

// Registra com a chave da interface (PSR-3)
$container->set(LoggerInterface::class, $loggerFactory);

// Registra serviços comuns
$container->set(PDO::class, function() {
    return Database::getConnection();
});

$container->set(App\Core\ViewRenderer::class, function() {
    return new App\Core\ViewRenderer(VIEW_DIR);
});

// ============== REPOSITÓRIOS ==============
$container->set(App\Repository\UsuarioRepository::class, function($c) {
    return new App\Repository\UsuarioRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(App\Repository\AdministradorRepository::class, function($c) {
    return new App\Repository\AdministradorRepository($c->get(PDO::class));
});

$container->set(App\Repository\LoginAttemptRepository::class, function($c) {
    return new App\Repository\LoginAttemptRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(App\Repository\TwoFactorRepository::class, function($c) {
    return new App\Repository\TwoFactorRepository($c->get(PDO::class), $c->get(LoggerInterface::class));
});

$container->set(App\Repository\AnuncioRepository::class, function($c) {
    return new App\Repository\AnuncioRepository($c->get(PDO::class));
});

$container->set(App\Repository\FotoRepository::class, function($c) {
    return new App\Repository\FotoRepository($c->get(PDO::class));
});

// ============== REPOSITÓRIOS DE VEÍCULOS ==============
$container->set(VeiculoRepository::class, function($c) {
    return new VeiculoRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(VeiculoCombustaoRepository::class, function($c) {
    return new VeiculoCombustaoRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(VeiculoEletricoRepository::class, function($c) {
    return new VeiculoEletricoRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(VeiculoHibridoRepository::class, function($c) {
    return new VeiculoHibridoRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(VeiculoGNVRepository::class, function($c) {
    return new VeiculoGNVRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(VeiculoOpcionalRepository::class, function($c) {
    return new VeiculoOpcionalRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(OpcionalRepository::class, function($c) {
    return new OpcionalRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(VeiculoImagemRepository::class, function($c) {
    return new VeiculoImagemRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(MarcaRepository::class, function($c) {
    return new MarcaRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(ModeloRepository::class, function($c) {
    return new ModeloRepository(
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

// Helpers
$container->set(App\Helper\ImagemHelper::class, function() {
    return new App\Helper\ImagemHelper($_ENV['UPLOAD_DIR']);
});

// ============== SERVIÇOS ==============
$container->set(App\Service\CriarAnuncioService::class, function($c) {
    return new App\Service\CriarAnuncioService(
        $c->get(App\Repository\VeiculoRepository::class),
        $c->get(App\Repository\AnuncioRepository::class),
        $c->get(App\Repository\FotoRepository::class),
        $c->get(App\Helper\ImagemHelper::class)
    );
});

$container->set(App\Service\MailService::class, function($c) {
    return new App\Service\MailService($c->get(LoggerInterface::class));
});

$container->set(App\Service\TwoFactorService::class, function($c) {
    return new App\Service\TwoFactorService(
        $c->get(App\Repository\TwoFactorRepository::class),
        $c->get(App\Service\MailService::class),
        $c->get(SessionInterface::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(App\Service\UserTwoFactorService::class, function($c) {
    return new App\Service\UserTwoFactorService(
        $c->get(App\Repository\TwoFactorRepository::class),
        $c->get(App\Service\MailService::class),
        $c->get(SessionInterface::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(App\Service\AdminAuthService::class, function($c) {
    return new App\Service\AdminAuthService(
        $c->get(App\Repository\AdministradorRepository::class),
        $c->get(SessionInterface::class),
        $c->get(LoggerInterface::class),
        $c->get(App\Repository\LoginAttemptRepository::class),
        $c->get(App\Service\TwoFactorService::class)
    );
});

$container->set(App\Service\AuthService::class, function($c) {
    return new App\Service\AuthService(
        $c->get(App\Repository\UsuarioRepository::class),
        $c->get(SessionInterface::class),
        $c->get(LoggerInterface::class),
        $c->get(App\Repository\LoginAttemptRepository::class),
        $c->get(App\Service\UserTwoFactorService::class)
    );
});

// ============== NOVO SERVIÇO DE VEÍCULOS ==============
$container->set(VeiculoService::class, function($c) {
    return new VeiculoService(
        $c->get(VeiculoRepository::class),
        $c->get(VeiculoCombustaoRepository::class),
        $c->get(VeiculoEletricoRepository::class),
        $c->get(VeiculoHibridoRepository::class),
        $c->get(VeiculoGNVRepository::class),
        $c->get(VeiculoOpcionalRepository::class),
        $c->get(OpcionalRepository::class),
        $c->get(VeiculoImagemRepository::class),
        $c->get(MarcaRepository::class),
        $c->get(ModeloRepository::class),
        $c->get(PDO::class),
        $c->get(LoggerInterface::class)
    );
});

// ============== MIDDLEWARES ==============
$container->set(App\Middleware\AdminMiddleware::class, function($c) {
    return new App\Middleware\AdminMiddleware($c->get(SessionInterface::class));
});

$container->set(App\Middleware\AuthMiddleware::class, function($c) {
    return new App\Middleware\AuthMiddleware(
        $c->get(SessionInterface::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(App\Middleware\TwoFactorMiddleware::class, function($c) {
    return new App\Middleware\TwoFactorMiddleware(
        $c->get(SessionInterface::class),
        $c->get(LoggerInterface::class)
    );
});

$container->set(App\Middleware\UserTwoFactorMiddleware::class, function($c) {
    return new App\Middleware\UserTwoFactorMiddleware(
        $c->get(SessionInterface::class),
        $c->get(LoggerInterface::class)
    );
});

// ============== CSRF ==============
$container->set(CsrfTokenGeneratorInterface::class, function() {
    return new CsrfTokenGenerator();
});

$container->set(CsrfTokenMiddleware::class, function($c) {
    return new CsrfTokenMiddleware(
        $c->get(SessionInterface::class),
        $c->get(CsrfTokenGeneratorInterface::class)
    );
});

$container->set(CsrfValidationMiddleware::class, function($c) {
    return new CsrfValidationMiddleware(
        $c->get(SessionInterface::class),
        $c->get(LoggerInterface::class)
    );
});

// ============== SESSÃO ==============
$container->set(SessionInterface::class, function() {
    return new SessionWrapper();
});

// ============== CRIAÇÃO DA REQUEST E REGISTRO NO CONTAINER ==============
$container->set(Request::class, function() {
    return new Request();
});

// ============== REGISTRO DAS FORMREQUESTS ==============
$container->set(LoginRequest::class, function($c) {
    return new LoginRequest($c->get(Request::class));
});

$container->set(TwoFactorRequest::class, function($c) {
    return new TwoFactorRequest($c->get(Request::class));
});

// ============== NOVAS FORMREQUESTS DE VEÍCULOS ==============
$container->set(VeiculoRequest::class, function($c) {
    return new VeiculoRequest(
        $c->get(Request::class),
        $c->get(MarcaRepository::class),
        $c->get(ModeloRepository::class),
        $c->get(VeiculoRepository::class)
    );
});

$container->set(VeiculoCombustaoRequest::class, function($c) {
    return new VeiculoCombustaoRequest($c->get(Request::class));
});

$container->set(VeiculoEletricoRequest::class, function($c) {
    return new VeiculoEletricoRequest($c->get(Request::class));
});

$container->set(VeiculoHibridoRequest::class, function($c) {
    return new VeiculoHibridoRequest($c->get(Request::class));
});

$container->set(VeiculoGNVRequest::class, function($c) {
    return new VeiculoGNVRequest($c->get(Request::class));
});

$container->set(VeiculoOpcionalRequest::class, function($c) {
    return new VeiculoOpcionalRequest($c->get(Request::class));
});

$container->set(VeiculoImagemRequest::class, function($c) {
    return new VeiculoImagemRequest($c->get(Request::class));
});

// ============== CONTROLLERS ==============
$container->set(App\Controller\AdminAuthController::class, function($c) {
    return new App\Controller\AdminAuthController(
        $c->get(App\Service\AdminAuthService::class),
        $c->get(App\Core\ViewRenderer::class),
        $c->get(SessionInterface::class),
        $c->get(LoginRequest::class)
    );
});

$container->set(App\Controller\TwoFactorController::class, function($c) {
    return new App\Controller\TwoFactorController(
        $c->get(App\Service\TwoFactorService::class),
        $c->get(App\Core\ViewRenderer::class),
        $c->get(SessionInterface::class),
        $c->get(TwoFactorRequest::class)
    );
});

$container->set(App\Controller\UserTwoFactorController::class, function($c) {
    return new App\Controller\UserTwoFactorController(
        $c->get(App\Service\UserTwoFactorService::class),
        $c->get(App\Core\ViewRenderer::class),
        $c->get(SessionInterface::class),
        $c->get(TwoFactorRequest::class)
    );
});

$container->set(App\Controller\AuthController::class, function($c) {
    return new App\Controller\AuthController(
        $c->get(App\Service\AuthService::class),
        $c->get(App\Core\ViewRenderer::class),
        $c->get(SessionInterface::class),
        $c->get(LoginRequest::class)
    );
});

// ============== NOVO CONTROLLER DE VEÍCULOS ==============
$container->set(VeiculoController::class, function($c) {
    return new VeiculoController(
        $c->get(VeiculoService::class),
        $c->get(App\Core\ViewRenderer::class),
        $c->get(SessionInterface::class),
        $c->get(VeiculoRequest::class),
        $c->get(VeiculoCombustaoRequest::class),
        $c->get(VeiculoEletricoRequest::class),
        $c->get(VeiculoHibridoRequest::class),
        $c->get(VeiculoGNVRequest::class),
        $c->get(VeiculoOpcionalRequest::class),
        $c->get(VeiculoImagemRequest::class),
        $c->get(MarcaRepository::class),
        $c->get(ModeloRepository::class)
    );
});

// ============== ROTEADOR ==============
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

$router = new Router($container);

// Obtém a Request a partir do container (a mesma instância que será injetada)
$request = $container->get(Request::class);

// ============== ROTAS 2FA ADMIN ==============
// GET sem CSRF (apenas exibição)
$router->get('/admin/2fa', 'TwoFactorController@form');

// POST com CSRF
$router->group('/admin/2fa', function(Router $router) use ($container) {
    $router->middleware($container->get(CsrfValidationMiddleware::class));
    $router->post('/verify', 'TwoFactorController@verify');
    $router->post('/resend', 'TwoFactorController@resend');
});

// ============== ROTAS ADMINISTRATIVAS ==============

// Grupo /admin/login (com CSRF para GET e POST)
$router->group('/admin/login', function(Router $router) use ($container) {
    // Gera o token CSRF (para exibir no formulário)
    $router->middleware($container->get(CsrfTokenMiddleware::class));
    // Valida o token em requisições POST (e outros métodos que alteram estado)
    $router->middleware($container->get(CsrfValidationMiddleware::class));

    $router->get('', 'AdminAuthController@formLogin');
    $router->post('', 'AdminAuthController@login');
});

// Logout (GET, sem CSRF)
$router->get('/admin/logout', 'AdminAuthController@logout');

// Grupo /admin protegido (autenticação + CSRF)
$router->group('/admin', function(Router $router) use ($container) {
    $router->middleware($container->get(CsrfTokenMiddleware::class));
    $router->middleware($container->get(AdminMiddleware::class));
    $router->middleware($container->get(TwoFactorMiddleware::class));
    $router->middleware($container->get(CsrfValidationMiddleware::class));
    $router->get('', 'AdminAuthController@index');
});

// ============== ROTAS LOJISTA ==============

// Rotas de login (públicas, com CSRF)
$router->group('/logista/login', function(Router $router) use ($container) {
    $router->middleware($container->get(CsrfTokenMiddleware::class));
    $router->middleware($container->get(CsrfValidationMiddleware::class));
    $router->get('', 'AuthController@formLogin');
    $router->post('', 'AuthController@login');
});

// Logout (GET, sem CSRF)
$router->get('/logista/logout', 'AuthController@logout');

// ============== ROTAS 2FA LOJISTA ==============
// GET sem CSRF (apenas exibição)
$router->get('/logista/2fa', 'UserTwoFactorController@form');

// POST com CSRF
$router->group('/logista/2fa', function(Router $router) use ($container) {
    $router->middleware($container->get(CsrfValidationMiddleware::class));
    $router->post('/verify', 'UserTwoFactorController@verify');
    $router->post('/resend', 'UserTwoFactorController@resend');
});

// ============== ROTAS DE VEÍCULOS (LOJISTA) ==============
// Todas as rotas protegidas por autenticação, 2FA e CSRF
$router->group('/logista', function(Router $router) use ($container) {
    // Middlewares aplicados a todas as rotas deste grupo
    $router->middleware($container->get(CsrfTokenMiddleware::class));
    $router->middleware($container->get(AuthMiddleware::class));
    $router->middleware($container->get(UserTwoFactorMiddleware::class));
    $router->middleware($container->get(CsrfValidationMiddleware::class));

    // Dashboard
    $router->get('/dashboard', 'AuthController@index');

    // ====== ROTAS DE VEÍCULOS ======
    // Listagem (com suporte a filtros via ?filtro=)
    $router->get('/veiculos', 'VeiculoController@index');

    // Lixeira
    $router->get('/veiculos/lixeira', 'VeiculoController@trash');

    // Formulário de criação
    $router->get('/veiculos/criar', 'VeiculoController@create');

    // Salvar novo veículo
    $router->post('/veiculos/salvar', 'VeiculoController@store');

    // Formulário de edição
    $router->get('/veiculos/editar/{id}', 'VeiculoController@edit');

    // Atualizar veículo
    $router->post('/veiculos/atualizar/{id}', 'VeiculoController@update');

    // Soft delete (mover para lixeira)
    $router->post('/veiculos/deletar/{id}', 'VeiculoController@destroy');

    // Restaurar da lixeira
    $router->post('/veiculos/restaurar/{id}', 'VeiculoController@restore');

    // Toggle vitrine (ativar/desativar)
    $router->post('/veiculos/toggle-vitrine/{id}', 'VeiculoController@toggleVitrine');

    // Remover imagem individual (AJAX)
    $router->post('/veiculos/delete-imagem/{veiculoId}/{imagemId}', 'VeiculoController@deleteImagem');

    // ====== ROTAS DE ANÚNCIOS (mantidas para compatibilidade) ======
    $router->get('/anuncios', 'AnuncioController@listar');
    $router->get('/anuncios/criar', 'AnuncioController@formCriar');
    $router->post('/anuncios/criar', 'AnuncioController@criar');
});

// ---------- Rotas públicas ----------
$router->get('/', 'HomeController@index');
$router->get('/loja/{slug}', 'VitrineController@listar');
$router->get('/loja/{slug}/anuncio/{id}', 'VitrineController@detalhe');

// ============== DISPATCH COM TRATAMENTO DE EXCEÇÕES ==============
try {
    $router->dispatch($request);
} catch (\App\Exception\HttpNotFoundException $e) {
    http_response_code(404);

    // --- LOG 404 ---
    try {
        $logger = $container->get(LoggerInterface::class);
        $logger->warning('Página não encontrada', [
            'uri'    => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'message'=> $e->getMessage(),
        ]);
    } catch (\Throwable $logError) {
        error_log('404: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    }

    if ($request->isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Página não encontrada.', 'status' => 404]);
        exit;
    }
    $view = $container->get(App\Core\ViewRenderer::class);
    echo $view->render('erros/404', ['mensagem' => $e->getMessage()]);

} catch (\App\Exception\CsrfException $e) {
    http_response_code(403);

    // --- LOG ESPECÍFICO PARA CSRF ---
    try {
        $logger = $container->get(LoggerInterface::class);
        $logger->warning('Acesso negado por CSRF', [
            'uri'    => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'message'=> $e->getMessage(),
        ]);
    } catch (\Throwable $logError) {
        error_log('CSRF: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    }

    if ($request->isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['erro' => $e->getMessage(), 'status' => 403]);
        exit;
    }
    $view = $container->get(App\Core\ViewRenderer::class);
    echo $view->render('erros/403', ['mensagem' => $e->getMessage()]);

} catch (\App\Exception\ForbiddenException $e) {
    http_response_code(403);

    // --- LOG ESPECÍFICO PARA PERMISSÃO (não CSRF) ---
    try {
        $logger = $container->get(LoggerInterface::class);
        $logger->warning('Acesso negado por permissão', [
            'uri'    => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'message'=> $e->getMessage(),
        ]);
    } catch (\Throwable $logError) {
        error_log('403: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
    }

    if ($request->isAjax()) {
        header('Content-Type: application/json');
        echo json_encode(['erro' => $e->getMessage(), 'status' => 403]);
        exit;
    }
    $view = $container->get(App\Core\ViewRenderer::class);
    echo $view->render('erros/403', ['mensagem' => $e->getMessage()]);

} catch (\Throwable $e) {
    http_response_code(500);

    // --- LOG 500 COM MONOLOG ---
    try {
        $logger = $container->get(LoggerInterface::class);
        $logger->error($e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'uri'   => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method'=> $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        ]);
    } catch (\Throwable $logError) {
        error_log('Falha ao registrar log com Monolog: ' . $logError->getMessage());
        error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    }
    // --- FIM DO LOG ---

    if ($env === 'development') {
        echo '<h1>Erro 500</h1><pre>' . $e->getMessage() . '</pre>';
    } else {
        $view = $container->get(App\Core\ViewRenderer::class);
        echo $view->render('erros/500', []);
    }
}