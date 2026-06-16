<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class ViewRenderer
{
    /**
     * @param string $viewDir Caminho absoluto para o diretório de views (ex: VIEW_DIR)
     */
    public function __construct(private string $viewDir)
    {
    }

    /**
     * Renderiza uma view, extraindo as variáveis de $data para o escopo local.
     *
     * @param string $view Caminho relativo da view (ex: 'dashboard/index')
     *                     Sem extensão .php — o método acrescenta.
     * @param array  $data Dados a serem passados para a view.
     *
     * @return string O HTML renderizado.
     * @throws RuntimeException se o arquivo da view não for encontrado.
     */
    public function render(string $view, array $data = []): string
    {
        $file = $this->viewDir . '/' . $view . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException("View não encontrada: {$view}");
        }

        // Isola as variáveis em escopo local e captura a saída
        extract($data, EXTR_SKIP);

        ob_start();
        include $file;
        return ob_get_clean();
    }

    /**
     * Renderiza uma view dentro de um layout principal.
     *
     * @param string $view   Caminho relativo da view específica (ex: 'auth/login')
     * @param array  $data   Dados passados para a view específica
     * @param string $layout Caminho relativo do layout (ex: 'layouts/main')
     * @param array  $layoutData Dados extras para o layout (ex: ['title' => 'Login'])
     *
     * @return string HTML renderizado com layout.
     */
    public function renderWithLayout(
        string $view,
        array $data = [],
        string $layout = 'layouts/main',
        array $layoutData = []
    ): string {
        // Renderiza a view específica
        $content = $this->render($view, $data);

        // Adiciona o conteúdo aos dados do layout
        $layoutData['content'] = $content;

        // Renderiza o layout passando os dados combinados
        return $this->render($layout, $layoutData);
    }
}