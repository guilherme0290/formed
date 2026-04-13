<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\WhatsappInstancia;
use App\Services\WhatsappEvolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class WhatsappInstanciaController extends Controller
{
    protected const INSTANCE_NAMES = [
        WhatsappInstancia::TIPO_FINANCEIRO => 'Formed_Finaceiro',
        WhatsappInstancia::TIPO_OPERACIONAL => 'Formed_Operacional',
    ];

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;

        $data = $request->validate([
            'financeiro_numero' => ['nullable', 'string', 'max:30'],
            'operacional_numero' => ['nullable', 'string', 'max:30'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $baseUrl = (string) config('services.evolution.base_url');
        $apiKey = (string) config('services.evolution.api_key');

        if ($baseUrl === '' || $apiKey === '') {
            return back()
                ->withErrors([
                    'whatsapp_config' => 'A Evolution API não está configurada no ambiente. Defina EVOLUTION_API_BASE_URL e EVOLUTION_API_KEY.',
                ])
                ->withInput();
        }

        foreach (WhatsappInstancia::TIPOS as $tipo) {
            $numeroCampo = "{$tipo}_numero";

            $numero = trim((string) ($data[$numeroCampo] ?? ''));
            $instanceName = self::INSTANCE_NAMES[$tipo];
            $hasAnyData = $numero !== '';

            if (!$hasAnyData) {
                continue;
            }

            $instancia = WhatsappInstancia::query()->firstOrNew([
                'empresa_id' => $empresaId,
                'tipo' => $tipo,
            ]);

            $instancia->fill([
                'tipo' => $tipo,
                'provider' => 'evolution',
                'base_url' => $baseUrl,
                'api_key' => $apiKey,
                'channel' => 'Baileys',
                'token' => $instancia->token ?: strtoupper((string) Str::uuid()),
                'numero' => preg_replace('/\D+/', '', $numero) ?: $numero,
                'instance_name' => $instanceName,
                'ativo' => $request->boolean('ativo', true),
                'last_error' => null,
            ]);
            $instancia->save();
        }

        return redirect()
            ->route('master.email-caixas.index', ['tab' => 'whatsapp'])
            ->with('ok', 'Configuração de WhatsApp salva com sucesso.');
    }

    public function createInstance(Request $request, string $tipo, WhatsappEvolutionService $service): JsonResponse
    {
        return $this->errorResponse('A Formed não cria instâncias na Evolution. Cadastre a instância no painel da Evolution e use esta tela apenas para vincular, conectar e monitorar.');
    }

    public function connect(Request $request, string $tipo, WhatsappEvolutionService $service): JsonResponse
    {
        try {
            $instancia = $this->getInstanciaDaEmpresa($request, $tipo);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        }

        if (blank($instancia->instance_name)) {
            return $this->errorResponse('Crie a instância antes de tentar conectar.');
        }

        try {
            $json = $service->connect($instancia->instance_name, $instancia, null);

            $instancia->update([
                'last_state' => 'connecting',
                'last_status_at' => now(),
                'last_error' => null,
            ]);

            return response()->json(['ok' => true, 'tipo' => $instancia->tipo] + $json);
        } catch (Throwable $e) {
            return $this->handleThrowable($instancia, $e);
        }
    }

    public function status(Request $request, string $tipo, WhatsappEvolutionService $service): JsonResponse
    {
        try {
            $instancia = $this->getInstanciaDaEmpresa($request, $tipo);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        }

        if (blank($instancia->instance_name)) {
            return response()->json([
                'ok' => true,
                'tipo' => $instancia->tipo,
                'state' => 'closed',
            ]);
        }

        try {
            $state = $service->status($instancia->instance_name, $instancia);
            $instancia->update([
                'last_state' => $state,
                'last_status_at' => now(),
                'last_error' => null,
            ]);

            return response()->json([
                'ok' => true,
                'tipo' => $instancia->tipo,
                'state' => $state,
            ]);
        } catch (Throwable $e) {
            return $this->handleThrowable($instancia, $e);
        }
    }

    public function restart(Request $request, string $tipo, WhatsappEvolutionService $service): JsonResponse
    {
        try {
            $instancia = $this->getInstanciaDaEmpresa($request, $tipo);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        }

        if (blank($instancia->instance_name)) {
            return $this->errorResponse('Crie a instância antes de tentar reiniciar.');
        }

        try {
            $json = $service->restart($instancia->instance_name, $instancia);

            $instancia->update([
                'last_state' => 'connecting',
                'last_status_at' => now(),
                'last_error' => null,
            ]);

            return response()->json(['ok' => true, 'tipo' => $instancia->tipo] + $json);
        } catch (Throwable $e) {
            return $this->handleThrowable($instancia, $e);
        }
    }

    public function logout(Request $request, string $tipo, WhatsappEvolutionService $service): JsonResponse
    {
        try {
            $instancia = $this->getInstanciaDaEmpresa($request, $tipo);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage());
        }

        if (blank($instancia->instance_name)) {
            return $this->errorResponse('Crie a instância antes de tentar desconectar.');
        }

        try {
            $service->logout($instancia->instance_name, $instancia);

            $instancia->update([
                'last_state' => 'closed',
                'last_status_at' => now(),
                'last_error' => null,
            ]);

            return response()->json(['ok' => true, 'tipo' => $instancia->tipo]);
        } catch (Throwable $e) {
            return $this->handleThrowable($instancia, $e);
        }
    }

    protected function getInstanciaDaEmpresa(Request $request, string $tipo): WhatsappInstancia
    {
        abort_unless(in_array($tipo, WhatsappInstancia::TIPOS, true), 404);

        $empresaId = $request->user()->empresa_id ?? 1;

        $instancia = WhatsappInstancia::query()
            ->daEmpresa($empresaId)
            ->doTipo($tipo)
            ->first();

        if ($instancia) {
            return $instancia;
        }

        $tipoLabel = $tipo === WhatsappInstancia::TIPO_FINANCEIRO ? 'financeiro' : 'operacional';
        throw new RuntimeException("Nenhuma instância de WhatsApp {$tipoLabel} foi vinculada para esta empresa.");
    }

    protected function handleThrowable(WhatsappInstancia $instancia, Throwable $e): JsonResponse
    {
        $instancia->update([
            'last_error' => $e->getMessage(),
            'last_status_at' => now(),
        ]);

        return response()->json([
            'ok' => false,
            'tipo' => $instancia->tipo,
            'message' => $this->resolveProviderMessage($e),
        ], 422);
    }

    protected function resolveProviderMessage(Throwable $e): string
    {
        $message = $e->getMessage();
        $messageLower = strtolower($message);

        if (str_contains($messageLower, 'cannot put /instance/restart/')) {
            return 'A opção de reiniciar não está disponível na versão atual da Evolution deste ambiente.';
        }

        if (str_contains($messageLower, 'cannot post /instance/restart/')) {
            return 'A opção de reiniciar não está disponível na versão atual da Evolution deste ambiente.';
        }

        if (str_contains($messageLower, 'cannot delete /instance/logout/')) {
            return 'A opção de logout não está disponível na versão atual da Evolution deste ambiente.';
        }

        if (str_contains($messageLower, 'cannot post /instance/logout/')) {
            return 'A opção de logout não está disponível na versão atual da Evolution deste ambiente.';
        }

        if (str_contains($messageLower, 'instance is not connected')) {
            return 'Este WhatsApp já está desconectado.';
        }

        if (str_contains($messageLower, 'invalid integration')) {
            return 'A Evolution recusou a integração. Verifique a URL base e a API Key administrativa informadas.';
        }

        if (str_contains($messageLower, 'unauthorized')) {
            return 'A Evolution recusou a autenticação. Verifique se a API Key informada é válida para esta URL.';
        }

        if (str_contains($messageLower, 'no query results for model')) {
            return 'Nenhuma instância de WhatsApp foi vinculada para esta empresa.';
        }

        return $message;
    }

    protected function errorResponse(string $message): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
        ], 422);
    }
}
