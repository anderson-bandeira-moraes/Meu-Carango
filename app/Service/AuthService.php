<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Contracts\SessionInterface;
use App\Repository\UsuarioRepository;

/**
 * Serviço de autenticação e gestão de usuários (lojistas).
 * Contém todas as regras de negócio relacionadas a login, registro e logout.
 */
class AuthService
{
    public function __construct(
        private UsuarioRepository $usuarioRepository,
        private SessionInterface $session,
    ) {}

    /**
     * Tenta realizar o login do usuário.
     *
     * @param string $email
     * @param string $senha (plain text)
     * @return array{sucesso: bool, erro?: string, usuario?: array}
     */
    public function login(string $email, string $senha): array
    {
        // Busca o usuário pelo e-mail
        $usuario = $this->usuarioRepository->findByEmail($email);

        // Verifica credenciais
        if (!$usuario || !password_verify($senha, $usuario['senha_hash'])) {
            return [
                'sucesso' => false,
                'erro'    => 'E-mail ou senha inválidos.',
            ];
        }

        // Verifica se o usuário está ativo
        if (($usuario['status'] ?? '') !== 'ativo') {
            return [
                'sucesso' => false,
                'erro'    => 'Sua conta não está ativa. Entre em contato com o suporte.',
            ];
        }

        // Regenera o ID da sessão (segurança)
        $this->session->regenerate();

        // Armazena dados do usuário na sessão
        $this->session->set('user_id', $usuario['id']);
        $this->session->set('user_nome', $usuario['nome']);
        $this->session->set('user_email', $usuario['email']);
        $this->session->set('user_slug', $usuario['slug']);

        // Atualiza último login (opcional)
        $this->usuarioRepository->updateLastLogin($usuario['id']);

        return [
            'sucesso' => true,
            'usuario' => $usuario,
        ];
    }

    /**
     * Registra um novo usuário (lojista).
     *
     * @param array $dados Dados esperados: nome, email, senha, nome_loja, slug, telefone
     * @return array{sucesso: bool, erro?: string, id?: int}
     */
    public function registrar(array $dados): array
    {
        // Validações de negócio (duplicidade)
        if ($this->usuarioRepository->emailExists($dados['email'])) {
            return [
                'sucesso' => false,
                'erro'    => 'E-mail já cadastrado.',
            ];
        }

        if ($this->usuarioRepository->slugExists($dados['slug'])) {
            return [
                'sucesso' => false,
                'erro'    => 'Este slug já está em uso. Escolha outro.',
            ];
        }

        // Prepara os dados para inserção
        $hash = password_hash($dados['senha'], PASSWORD_DEFAULT);

        $novoUsuario = [
            'nome'       => $dados['nome'],
            'email'      => $dados['email'],
            'senha_hash' => $hash,
            'nome_loja'  => $dados['nome_loja'],
            'slug'       => $dados['slug'],
            'telefone'   => $dados['telefone'],
            'plano'      => 'teste',      // plano padrão
            'status'     => 'ativo',      // ativo por padrão
        ];

        $id = $this->usuarioRepository->create($novoUsuario);

        if (!$id) {
            return [
                'sucesso' => false,
                'erro'    => 'Erro ao criar conta. Tente novamente mais tarde.',
            ];
        }

        return [
            'sucesso' => true,
            'id'      => $id,
        ];
    }

    /**
     * Realiza logout (destrói a sessão).
     *
     * @return void
     */
    public function logout(): void
    {
        $this->session->destroy();
    }

    /**
     * Verifica se o usuário está autenticado (sessão ativa).
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->session->has('user_id');
    }

    /**
     * Retorna os dados do usuário logado atualmente (da sessão).
     *
     * @return array|null
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return [
            'id'    => $this->session->get('user_id'),
            'nome'  => $this->session->get('user_nome'),
            'email' => $this->session->get('user_email'),
            'slug'  => $this->session->get('user_slug'),
        ];
    }

    /**
     * Busca um usuário pelo ID (caso precise de dados completos do banco).
     *
     * @param int $id
     * @return array|null
     */
    public function getUserById(int $id): ?array
    {
        return $this->usuarioRepository->findById($id);
    }
}