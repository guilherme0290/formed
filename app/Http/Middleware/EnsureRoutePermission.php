<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if ($user->hasPapel('Master')) {
            return $next($request);
        }

        $route = $request->route();
        $routeName = (string) optional($route)->getName();
        if ($routeName === '') {
            return $next($request);
        }

        if (!$this->isRouteAllowedForRole($user, $routeName)) {
            if ($request->expectsJson()) {
                abort(403, 'Usuário sem permissão para acessar esta funcionalidade.');
            }

            return $this->redirectWithoutPermission($request, $user);
        }

        if ($user->hasPapel('Cliente')) {
            return $next($request);
        }

        $permissionKey = $this->resolvePermissionKey($routeName, $request->getMethod());
        if ($permissionKey === null || $this->userHasPermission($user, $permissionKey)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'Usuário sem permissão para acessar esta funcionalidade.');
        }

        return $this->redirectWithoutPermission($request, $user);
    }

    private function isRouteAllowedForRole(User $user, string $routeName): bool
    {
        if (in_array($routeName, ['dashboard', 'profile.edit', 'profile.update', 'profile.destroy', 'estados.cidades'], true)) {
            return true;
        }

        // Permite rotas do módulo master para qualquer papel;
        // a autorização fina continua na checagem de permissões logo abaixo no handle().
        if (str_starts_with($routeName, 'master.')) {
            return true;
        }

        if ($user->hasPapel('Cliente')) {
            return str_starts_with($routeName, 'cliente.')
                || $this->isClienteOperationalServiceRoute($routeName);
        }

        if ($user->hasPapel('Operacional')) {
            return str_starts_with($routeName, 'operacional.');
        }

        if ($user->hasPapel('Comercial')) {
            return str_starts_with($routeName, 'comercial.');
        }

        if ($user->hasPapel('Financeiro')) {
            return str_starts_with($routeName, 'financeiro.');
        }

        if ($user->hasPapel('Master')) {
            return true;
        }

        return false;
    }

    private function isClienteOperationalServiceRoute(string $routeName): bool
    {
        if ($routeName === 'operacional.funcoes.store-ajax') {
            return true;
        }

        $allowedPrefixes = [
            'operacional.kanban.aso.',
            'operacional.kanban.pgr.',
            'operacional.kanban.pcmso.',
            'operacional.pgr.',
            'operacional.pcmso.',
            'operacional.ltcat.',
            'operacional.apr.',
            'operacional.treinamentos-nr.',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function userHasPermission(User $user, string $permissionKey): bool
    {
        $keys = [$permissionKey];

        if (str_starts_with($permissionKey, 'master.tabela-precos.')) {
            $keys[] = str_replace('master.tabela-precos.', 'comercial.tabela-precos.', $permissionKey);
        } elseif (str_starts_with($permissionKey, 'comercial.tabela-precos.')) {
            $keys[] = str_replace('comercial.tabela-precos.', 'master.tabela-precos.', $permissionKey);
        }

        $viaPapel = $user->papel()
            ->whereHas('permissoes', fn ($q) => $q->whereIn('chave', $keys))
            ->exists();

        if ($viaPapel) {
            return true;
        }

        return $user->permissoesDiretas()
            ->whereIn('chave', $keys)
            ->exists();
    }

    private function resolvePermissionKey(string $routeName, string $method): ?string
    {
        if (str_starts_with($routeName, 'comercial.')) {
            return $this->resolveComercialPermission($routeName, $method);
        }

        if (str_starts_with($routeName, 'financeiro.')) {
            return $this->resolveFinanceiroPermission($routeName, $method);
        }

        if (str_starts_with($routeName, 'operacional.')) {
            return $this->resolveOperacionalPermission($routeName, $method);
        }

        if (str_starts_with($routeName, 'cliente.')) {
            return $this->resolveClientePermission($routeName, $method);
        }

        if (str_starts_with($routeName, 'master.')) {
            return $this->resolveMasterPermission($routeName, $method);
        }

        if (str_starts_with($routeName, 'clientes.')) {
            return $this->resolveCrudPermission('master.clientes', $routeName, $method);
        }

        if ($routeName === 'estados.cidades') {
            return 'master.clientes.view';
        }

        return null;
    }

    private function resolveComercialPermission(string $routeName, string $method): ?string
    {
        if ($routeName === 'comercial.dashboard') {
            return 'comercial.dashboard.view';
        }

        if (str_starts_with($routeName, 'comercial.propostas.') || $routeName === 'comercial.propostas') {
            return $this->resolveCrudPermission('comercial.propostas', $routeName, $method);
        }

        if (str_starts_with($routeName, 'comercial.pipeline.')) {
            if ($routeName === 'comercial.pipeline.mover') {
                return 'comercial.propostas.update';
            }

            return 'comercial.pipeline.view';
        }

        if (str_starts_with($routeName, 'comercial.clientes.')) {
            return $this->resolveCrudPermission('comercial.clientes', $routeName, $method);
        }

        if (str_starts_with($routeName, 'comercial.contratos.')) {
            return $this->resolveCrudPermission('comercial.contratos', $routeName, $method);
        }

        if ($routeName === 'comercial.tabela-precos.update') {
            return 'comercial.tabela-precos.update';
        }

        if ($routeName === 'comercial.tabela-precos.index' || str_starts_with($routeName, 'comercial.tabela-precos.')) {
            return $this->resolveCrudPermission('comercial.tabela-precos', $routeName, $method);
        }

        if (str_starts_with($routeName, 'comercial.comissoes.')) {
            return 'comercial.comissoes.view';
        }

        if (str_starts_with($routeName, 'comercial.agenda.')) {
            return in_array($method, ['GET', 'HEAD'], true)
                ? 'comercial.agenda.view'
                : 'comercial.agenda.edit';
        }

        if (str_starts_with($routeName, 'comercial.apresentacao.')) {
            return $this->resolveCrudPermission('comercial.propostas', $routeName, $method);
        }

        if (
            str_starts_with($routeName, 'comercial.esocial.') ||
            str_starts_with($routeName, 'comercial.exames.') ||
            str_starts_with($routeName, 'comercial.medicoes.') ||
            str_starts_with($routeName, 'comercial.protocolos-exames.') ||
            str_starts_with($routeName, 'comercial.ghes.') ||
            str_starts_with($routeName, 'comercial.clientes-ghes.') ||
            str_starts_with($routeName, 'comercial.treinamentos-nrs.') ||
            str_starts_with($routeName, 'comercial.clientes-aso-grupos.')
        ) {
            return $this->resolveCrudPermission('comercial.tabela-precos', $routeName, $method);
        }

        return null;
    }

    private function resolveFinanceiroPermission(string $routeName, string $method): ?string
    {
        if ($routeName === 'financeiro.dashboard') {
            return 'financeiro.dashboard.view';
        }

        if (
            $routeName === 'financeiro.contas-receber' ||
            str_starts_with($routeName, 'financeiro.contas-receber.')
        ) {
            if (in_array($routeName, [
                'financeiro.contas-receber.baixar',
                'financeiro.contas-receber.update-datas',
                'financeiro.contas-receber.reabrir',
                'financeiro.contas-receber.boleto',
            ], true)) {
                return 'financeiro.contas-receber.update';
            }

            if ($routeName === 'financeiro.contas-receber.itens') {
                return 'financeiro.contas-receber.view';
            }

            return $this->resolveCrudPermission('financeiro.contas-receber', $routeName, $method);
        }

        if ($routeName === 'financeiro.contratos' || str_starts_with($routeName, 'financeiro.contratos.')) {
            return $this->resolveCrudPermission('financeiro.contratos', $routeName, $method);
        }

        if ($routeName === 'financeiro.faturamento-detalhado' || str_starts_with($routeName, 'financeiro.faturamento-detalhado.')) {
            return $this->resolveCrudPermission('financeiro.faturamento', $routeName, $method);
        }

        return null;
    }

    private function resolveOperacionalPermission(string $routeName, string $method): ?string
    {
        if ($routeName === 'operacional.kanban' || $routeName === 'operacional.painel') {
            return 'operacional.dashboard.view';
        }

        if (str_starts_with($routeName, 'operacional.anexos.')) {
            return 'operacional.anexos.manage';
        }

        if (str_starts_with($routeName, 'operacional.tarefas.')) {
            return 'operacional.tarefas.manage';
        }

        $module = $this->resolveOperacionalModule($routeName);
        if ($module !== null) {
            return $this->resolveCrudPermission("operacional.{$module}", $routeName, $method);
        }

        if (str_starts_with($routeName, 'operacional.funcoes.')) {
            return 'operacional.tarefas.manage';
        }

        return null;
    }

    private function resolveClientePermission(string $routeName, string $method): ?string
    {
        if (in_array($routeName, ['cliente.dashboard', 'cliente.faturas', 'cliente.agendamentos', 'cliente.andamento', 'cliente.arquivos.index'], true)) {
            return 'cliente.dashboard.view';
        }

        if (str_starts_with($routeName, 'cliente.funcionarios.')) {
            if ($routeName === 'cliente.funcionarios.toggle-status') {
                return 'cliente.funcionarios.toggle';
            }

            if (in_array($routeName, ['cliente.funcionarios.create', 'cliente.funcionarios.store'], true)) {
                return 'cliente.funcionarios.create';
            }

            if (in_array($routeName, ['cliente.funcionarios.edit', 'cliente.funcionarios.update', 'cliente.funcionarios.destroy'], true)) {
                return 'cliente.funcionarios.update';
            }

            return 'cliente.funcionarios.view';
        }

        if (str_starts_with($routeName, 'cliente.servicos.')) {
            return $routeName;
        }

        if (
            str_starts_with($routeName, 'cliente.arquivos.download') ||
            str_starts_with($routeName, 'cliente.arquivos.funcionario.download')
        ) {
            return 'cliente.dashboard.view';
        }

        return null;
    }

    private function resolveClienteProxyPermission(string $routeName): ?string
    {
        if (str_starts_with($routeName, 'operacional.kanban.aso.')) {
            return 'cliente.servicos.aso';
        }

        if (str_starts_with($routeName, 'operacional.kanban.pgr.') || str_starts_with($routeName, 'operacional.pgr.')) {
            return 'cliente.servicos.pgr';
        }

        if (str_starts_with($routeName, 'operacional.kanban.pcmso.') || str_starts_with($routeName, 'operacional.pcmso.')) {
            return 'cliente.servicos.pcmso';
        }

        if (str_starts_with($routeName, 'operacional.ltcat.')) {
            return 'cliente.servicos.ltcat';
        }

        if (str_starts_with($routeName, 'operacional.apr.')) {
            return 'cliente.servicos.apr';
        }

        if (str_starts_with($routeName, 'operacional.treinamentos-nr.')) {
            return 'cliente.servicos.treinamentos';
        }

        return null;
    }

    private function resolveMasterPermission(string $routeName, string $method): ?string
    {
        if ($routeName === 'master.dashboard') {
            return 'master.dashboard.view';
        }

        if (
            str_starts_with($routeName, 'master.acessos') ||
            str_starts_with($routeName, 'master.usuarios.')
        ) {
            return 'master.acessos.manage';
        }

        if (str_starts_with($routeName, 'master.papeis.')) {
            return 'master.papeis.manage';
        }

        if (str_starts_with($routeName, 'master.permissoes.')) {
            return 'master.permissoes.manage';
        }

        if ($routeName === 'master.tabela-precos.update') {
            return 'master.tabela-precos.update';
        }

        if ($routeName === 'master.tabela-precos.index' || str_starts_with($routeName, 'master.tabela-precos.')) {
            return $this->resolveCrudPermission('master.tabela-precos', $routeName, $method);
        }

        if (str_starts_with($routeName, 'master.comissoes.')) {
            return in_array($method, ['GET', 'HEAD'], true)
                ? 'master.comissoes.view'
                : 'master.comissoes.update';
        }

        if (
            str_starts_with($routeName, 'master.agenda-vendedores.') ||
            str_starts_with($routeName, 'master.agendamentos') ||
            str_starts_with($routeName, 'master.relatorios') ||
            str_starts_with($routeName, 'master.relatorio-') ||
            str_starts_with($routeName, 'master.dashboard-preferences.')
        ) {
            return 'master.dashboard.view';
        }

        if (
            str_starts_with($routeName, 'master.empresa.') ||
            str_starts_with($routeName, 'master.email-caixas.') ||
            str_starts_with($routeName, 'master.funcoes.') ||
            str_starts_with($routeName, 'master.esocial.') ||
            str_starts_with($routeName, 'master.exames.') ||
            str_starts_with($routeName, 'master.medicoes.') ||
            str_starts_with($routeName, 'master.protocolos-exames.') ||
            str_starts_with($routeName, 'master.ghes.') ||
            str_starts_with($routeName, 'master.clientes-ghes.') ||
            str_starts_with($routeName, 'master.treinamentos-nrs.') ||
            str_starts_with($routeName, 'master.tempo-tarefas.')
        ) {
            return 'master.dashboard.view';
        }

        return null;
    }

    private function resolveCrudPermission(string $prefix, string $routeName, string $method): string
    {
        if ($method === 'DELETE') {
            return "{$prefix}.delete";
        }

        if (in_array($method, ['PUT', 'PATCH'], true)) {
            return "{$prefix}.update";
        }

        if ($method === 'POST') {
            return "{$prefix}.create";
        }

        if (
            str_ends_with($routeName, '.create') ||
            str_contains($routeName, '.create.') ||
            str_ends_with($routeName, '.store')
        ) {
            return "{$prefix}.create";
        }

        if (
            str_ends_with($routeName, '.edit') ||
            str_contains($routeName, '.edit.') ||
            str_ends_with($routeName, '.update') ||
            str_contains($routeName, '.update.') ||
            str_ends_with($routeName, '.destroy') ||
            str_contains($routeName, '.destroy.')
        ) {
            return "{$prefix}.update";
        }

        return "{$prefix}.view";
    }

    private function resolveOperacionalModule(string $routeName): ?string
    {
        $map = [
            'operacional.kanban.aso.' => 'aso',
            'operacional.kanban.pgr.' => 'pgr',
            'operacional.kanban.pcmso.' => 'pcmso',
            'operacional.apr.' => 'apr',
            'operacional.pgr.' => 'pgr',
            'operacional.pcmso.' => 'pcmso',
            'operacional.ltcat.' => 'ltcat',
            'operacional.ltip.' => 'ltip',
            'operacional.pae.' => 'pae',
            'operacional.treinamentos-nr.' => 'treinamentos',
            'operacional.clientes.funcionarios.' => 'aso',
        ];

        foreach ($map as $prefix => $module) {
            if (str_starts_with($routeName, $prefix)) {
                return $module;
            }
        }

        return null;
    }

    private function redirectWithoutPermission(Request $request, User $user): RedirectResponse
    {
        $fallback = $this->fallbackRouteByRole($user);
        $previous = url()->previous();
        $current = $request->fullUrl();

        $target = $fallback;
        if (!empty($previous) && $previous !== $current) {
            $target = $previous;
        }

        return redirect()->to($target)->with('error', 'Usuário sem permissão para acessar esta tela.');
    }

    private function fallbackRouteByRole(User $user): string
    {
        if ($user->hasPapel('Comercial')) {
            return route('comercial.dashboard');
        }

        if ($user->hasPapel('Financeiro')) {
            return route('financeiro.dashboard');
        }

        if ($user->hasPapel('Operacional')) {
            return route('operacional.kanban');
        }

        if ($user->hasPapel('Cliente')) {
            return route('cliente.dashboard');
        }

        return route('dashboard');
    }
}
