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
$dotenv->safeLoad(); // não falha se .env não existir, útil em produção com vars de ambiente reais

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

$container = new Container();

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

// Services
$container->set(App\Service\CriarAnuncioService::class, function($c) {
    return new App\Service\CriarAnuncioService(
        $c->get(App\Repository\VeiculoRepository::class),
        $c->get(App\Repository\AnuncioRepository::class),
        $c->get(App\Repository\FotoRepository::class),
        $c->get(App\Helper\ImagemHelper::class)
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
        $c->get(SessionInterface::class)
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
$container->set(App\Middleware\AdminMiddleware::class, function($c) {
    return new App\Middleware\AdminMiddleware($c->get(SessionInterface::class));
});

$container->set(App\Repository\AdministradorRepository::class, function($c) {
    return new App\Repository\AdministradorRepository($c->get(PDO::class));
});

$container->set(App\Service\AdminAuthService::class, function($c) {
    return new App\Service\AdminAuthService(
        $c->get(App\Repository\AdministradorRepository::class),
        $c->get(SessionInterface::class)
    );
});

$container->set(App\Controller\AdminAuthController::class, function($c) {
    return new App\Controller\AdminAuthController(
        $c->get(App\Service\AdminAuthService::class),
        $c->get(App\Core\ViewRenderer::class),
        $c->get(SessionInterface::class)
    );
});

// ============== ROTEADOR ==============
use App\Core\Router;
use App\Core\Request;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\AdminMiddleware;

$router  = new Router($container);
$request = new Request(); 

// ============== ROTAS ADMINISTRATIVAS ==============
$router->get('/admin/login', 'AdminAuthController@formLogin');
$router->post('/admin/login', 'AdminAuthController@login');
$router->get('/admin/logout', 'AdminAuthController@logout');

$adminMiddleware = $container->get(App\Middleware\AdminMiddleware::class);

$router->group('/admin', function(Router $router) use ($container) {
    $router->middleware($container->get(AdminMiddleware::class));
    $router->get('', 'AdminAuthController@index');
});

// ---------- Rotas públicas ----------
$router->get('/', 'HomeController@index');
$router->get('/loja/{slug}', 'VitrineController@listar');
$router->get('/loja/{slug}/anuncio/{id}', 'VitrineController@detalhe');

// ---------- Rotas de autenticação ----------
$router->get('/login', 'AuthController@formLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/registro', 'AuthController@formRegistro');
$router->post('/registro', 'AuthController@registrar');

// ---------- Rotas protegidas (dashboard) ----------
$router->group('/dashboard', function(Router $router) use ($container) {
    $router->middleware($container->get(AuthMiddleware::class));
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
    $view = $container->get(App\Core\ViewRenderer::class);
    echo $view->render('erros/404', ['mensagem' => $e->getMessage()]);
} catch (\App\Exception\ForbiddenException $e) {
    http_response_code(403);
    $view = $container->get(App\Core\ViewRenderer::class);
    echo $view->render('erros/403', ['mensagem' => $e->getMessage()]);
} catch (\Throwable $e) {
    http_response_code(500);
    if ($env === 'development') {
        echo '<h1>Erro 500</h1><pre>' . $e->getMessage() . '</pre>';
    } else {
        $view = $container->get(App\Core\ViewRenderer::class);
        echo $view->render('erros/500', []);
    }
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}