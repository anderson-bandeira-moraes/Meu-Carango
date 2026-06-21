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
use App\Repository\TwoFactorRepository;
use App\Service\MailService;
use App\Service\TwoFactorService;
use App\Middleware\TwoFactorMiddleware;
use App\Controller\TwoFactorController;        

$container = new Container();

// Carrega a configuração de logging
$logConfig = require CONFIG_DIR . '/logging.php';

// Registra o Logger no container
$container->set(\Monolog\Logger::class, function() use ($logConfig) {
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
});

// Registra serviços comuns
$container->set(PDO::class, function() {
    return Database::getConnection();
});

$container->set(App\Core\ViewRenderer::class, function() {
    return new App\Core\ViewRenderer(VIEW_DIR);
});

// Repositórios
$container->set(App\Repository\UsuarioRepository::class, function($c) {
    return new App\Repository\UsuarioRepository($c->get(PDO::class));
});

$container->set(App\Repository\VeiculoRepository::class, function($c) {
    return new App\Repository\VeiculoRepository($c->get(PDO::class));
});

$container->set(App\Repository\AnuncioRepository::class, function($c) {
    return new App\Repository\AnuncioRepository($c->get(PDO::class));
});

$container->set(App\Repository\FotoRepository::class, function($c) {
    return new App\Repository\FotoRepository($c->get(PDO::class));
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
    return new App\Service\MailService($c->get(\Monolog\Logger::class));
});

$container->set(App\Service\TwoFactorService::class, function($c) {
    return new App\Service\TwoFactorService(
        $c->get(App\Repository\TwoFactorRepository::class),
        $c->get(App\Service\MailService::class),
        $c->get(SessionInterface::class),
        $c->get(\Monolog\Logger::class)
    );
});

// Registra o wrapper da sessão (substitui o acesso direto estático)
$container->set(SessionInterface::class, function() {
    return new SessionWrapper();
});

// ============== CSRF ==============
// Gerador de token CSRF (abstração)
$container->set(CsrfTokenGeneratorInterface::class, function() {
    return new CsrfTokenGenerator();
});

// Middleware de geração do token
$container->set(CsrfTokenMiddleware::class, function($c) {
    return new CsrfTokenMiddleware(
        $c->get(SessionInterface::class),
        $c->get(CsrfTokenGeneratorInterface::class)
    );
});

// Middleware de validação do token
$container->set(CsrfValidationMiddleware::class, function($c) {
    return new CsrfValidationMiddleware(
        $c->get(SessionInterface::class),
        $c->get(Logger::class) 
    );
});

$container->set(App\Middleware\TwoFactorMiddleware::class, function($c) {
    return new App\Middleware\TwoFactorMiddleware(
        $c->get(SessionInterface::class),
        $c->get(\Monolog\Logger::class)
    );
});

// ============== AUTENTICAÇÃO (LOJISTA) ==============
$container->set(App\Middleware\AuthMiddleware::class, function($c) {
    return new App\Middleware\AuthMiddleware(
        $c->get(SessionInterface::class)
    );
});

$container->set(App\Service\AuthService::class, function($c) {
    return new App\Service\AuthService(
        $c->get(App\Repository\UsuarioRepository::class),
        $c->get(SessionInterface::class)
    );
});

$container->set(App\Controller\AuthController::class, function($c) {
    return new App\Controller\AuthController(
        $c->get(App\Service\AuthService::class),
        $c->get(App\Core\ViewRenderer::class),
        $c->get(SessionInterface::class)
    );
});

// ============== ADMINISTRAÇÃO ==============

// Middleware de administrador
$container->set(App\Middleware\AdminMiddleware::class, function($c) {
    return new App\Middleware\AdminMiddleware($c->get(SessionInterface::class));
});

// Repositórios
$container->set(App\Repository\AdministradorRepository::class, function($c) {
    return new App\Repository\AdministradorRepository($c->get(PDO::class));
});

$container->set(App\Repository\LoginAttemptRepository::class, function($c) {
    return new App\Repository\LoginAttemptRepository(
        $c->get(PDO::class),
        $c->get(\Monolog\Logger::class)
    );
});

$container->set(App\Repository\TwoFactorRepository::class, function($c) {
    return new App\Repository\TwoFactorRepository($c->get(PDO::class), $c->get(\Monolog\Logger::class));
});

// Services
$container->set(App\Service\AdminAuthService::class, function($c) {
    return new App\Service\AdminAuthService(
        $c->get(App\Repository\AdministradorRepository::class),
        $c->get(SessionInterface::class),
        $c->get(\Monolog\Logger::class),
        $c->get(App\Repository\LoginAttemptRepository::class),
        $c->get(App\Service\TwoFactorService::class)
    );
});

// ============== CONTROLLERS ==============
$container->set(App\Controller\AdminAuthController::class, function($c) {
    return new App\Controller\AdminAuthController(
        $c->get(App\Service\AdminAuthService::class),
        $c->get(App\Core\ViewRenderer::class),
        $c->get(SessionInterface::class)
    );
});

$container->set(App\Controller\TwoFactorController::class, function($c) {
    return new App\Controller\TwoFactorController(
        $c->get(App\Service\TwoFactorService::class),
        $c->get(App\Core\ViewRenderer::class),
        $c->get(SessionInterface::class),
        $c->get(\Monolog\Logger::class)
    );
});

// ============== ROTEADOR ==============
use App\Core\Router;
use App\Core\Request;
use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;

$router  = new Router($container);
$request = new Request(); 

// ============== ROTAS 2FA ==============
$router->get('/admin/2fa', 'TwoFactorController@form');
$router->post('/admin/2fa/verify', 'TwoFactorController@verify');
$router->post('/admin/2fa/resend', 'TwoFactorController@resend');

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

// ---------- Rotas públicas ----------
$router->get('/', 'HomeController@index');
$router->get('/loja/{slug}', 'VitrineController@listar');
$router->get('/loja/{slug}/anuncio/{id}', 'VitrineController@detalhe');

// ---------- Rotas de autenticação (com CSRF) ----------
$router->group('/login', function(Router $router) use ($container) {
    $router->middleware($container->get(CsrfTokenMiddleware::class));
    $router->get('', 'AuthController@formLogin');
    $router->post('', 'AuthController@login');
});

$router->group('/registro', function(Router $router) use ($container) {
    $router->middleware($container->get(CsrfTokenMiddleware::class));
    $router->get('', 'AuthController@formRegistro');
    $router->post('', 'AuthController@registrar');
});

$router->get('/logout', 'AuthController@logout');

// ---------- Rotas protegidas (dashboard) com CSRF ----------
$router->group('/dashboard', function(Router $router) use ($container) {
    $router->middleware($container->get(CsrfTokenMiddleware::class));
    $router->middleware($container->get(AuthMiddleware::class));
    $router->middleware($container->get(CsrfValidationMiddleware::class));
    
    $router->get('', 'DashboardController@index');
    $router->get('/veiculos', 'VeiculoController@listar');
    $router->get('/veiculos/criar', 'VeiculoController@formCriar');
    $router->post('/veiculos/criar', 'VeiculoController@criar');
    $router->get('/anuncios', 'AnuncioController@listar');
    $router->get('/anuncios/criar', 'AnuncioController@formCriar');
    $router->post('/anuncios/criar', 'AnuncioController@criar');
});

// ============== DISPATCH COM TRATAMENTO DE EXCEÇÕES ==============
try {
    $router->dispatch($request);
} catch (\App\Exception\HttpNotFoundException $e) {
    http_response_code(404);

    // --- LOG 404 ---
    try {
        $logger = $container->get(\Monolog\Logger::class);
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
        $logger = $container->get(\Monolog\Logger::class);
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
        $logger = $container->get(\Monolog\Logger::class);
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
        $logger = $container->get(\Monolog\Logger::class);
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