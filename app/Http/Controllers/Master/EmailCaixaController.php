<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\EmailCaixa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

class EmailCaixaController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id ?? 1;

        $caixas = EmailCaixa::query()
            ->where('empresa_id', $empresaId)
            ->orderByDesc('ativo')
            ->orderBy('nome')
            ->get();

        return view('master.email-caixas.index', compact('caixas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $requerAuth = $request->boolean('requer_autenticacao');

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'host' => ['required', 'string', 'max:255'],
            'porta' => ['required', 'integer', 'min:1', 'max:65535'],
            'criptografia' => ['required', Rule::in(['none', 'ssl', 'starttls', 'tls'])],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:600'],
            'requer_autenticacao' => ['sometimes', 'boolean'],
            'usuario' => [$requerAuth ? 'required' : 'nullable', 'string', 'max:255'],
            'senha' => [$requerAuth ? 'required' : 'nullable', 'string', 'max:255'],
            'ativo' => ['sometimes', 'boolean'],
        ]);

        $criptografia = $data['criptografia'] === 'tls' ? 'starttls' : $data['criptografia'];

        EmailCaixa::create([
            'empresa_id' => $empresaId,
            'nome' => $data['nome'],
            'host' => $data['host'],
            'porta' => $data['porta'],
            'criptografia' => $criptografia,
            'timeout' => $data['timeout'] ?? null,
            'requer_autenticacao' => $requerAuth,
            'usuario' => $requerAuth ? ($data['usuario'] ?? null) : null,
            'senha' => $requerAuth ? ($data['senha'] ?? null) : null,
            'ativo' => $request->boolean('ativo', true),
            'created_by' => $request->user()->id ?? null,
        ]);

        return redirect()
            ->route('master.email-caixas.index')
            ->with('ok', 'Caixa de email cadastrada com sucesso.');
    }

    public function update(Request $request, EmailCaixa $emailCaixa): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->assertEmpresa($emailCaixa, $empresaId);

        $data = $request->validate([
            'usuario' => ['required', 'email', 'max:255'],
            'senha' => ['nullable', 'string', 'max:255'],
        ]);

        $emailCaixa->update([
            'usuario' => $data['usuario'],
            'senha' => $request->filled('senha') ? $data['senha'] : $emailCaixa->senha,
        ]);

        return redirect()
            ->route('master.email-caixas.index')
            ->with('ok', 'Caixa de email atualizada com sucesso.');
    }

    public function destroy(Request $request, EmailCaixa $emailCaixa): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->assertEmpresa($emailCaixa, $empresaId);

        $emailCaixa->delete();

        return redirect()
            ->route('master.email-caixas.index')
            ->with('ok', 'Email excluido com sucesso.');
    }

    public function testar(Request $request): RedirectResponse
    {
        $requerAuth = $request->boolean('requer_autenticacao');

        $data = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'host' => ['required', 'string', 'max:255'],
            'porta' => ['required', 'integer', 'min:1', 'max:65535'],
            'criptografia' => ['required', Rule::in(['none', 'ssl', 'starttls', 'tls'])],
            'timeout' => ['nullable', 'integer', 'min:1', 'max:600'],
            'requer_autenticacao' => ['sometimes', 'boolean'],
            'usuario' => [$requerAuth ? 'required' : 'nullable', 'string', 'max:255'],
            'senha' => [$requerAuth ? 'required' : 'nullable', 'string', 'max:255'],
        ]);

        try {
            $this->executarTesteSmtp([
                'host' => $data['host'],
                'porta' => $data['porta'],
                'criptografia' => $data['criptografia'],
                'timeout' => $data['timeout'] ?? null,
                'requer_autenticacao' => $requerAuth,
                'usuario' => $requerAuth ? ($data['usuario'] ?? null) : null,
                'senha' => $requerAuth ? ($data['senha'] ?? null) : null,
            ]);
        } catch (TransportExceptionInterface $e) {
            return back()
                ->withInput()
                ->with('smtp_error', 'Falha ao conectar: '.$e->getMessage());
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('smtp_error', 'Erro inesperado ao testar conexao.');
        }

        return back()
            ->withInput()
            ->with('smtp_ok', 'Conexao bem-sucedida.');
    }

    public function testarSalvo(Request $request, EmailCaixa $emailCaixa): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->assertEmpresa($emailCaixa, $empresaId);

        try {
            $this->executarTesteSmtp($this->getConfigFromModel($emailCaixa));
        } catch (TransportExceptionInterface $e) {
            return back()
                ->with('smtp_error', 'Falha ao conectar: '.$e->getMessage());
        } catch (\Throwable $e) {
            return back()
                ->with('smtp_error', 'Erro inesperado ao testar conexao.');
        }

        return back()
            ->with('smtp_ok', 'Conexao bem-sucedida.');
    }

    public function enviarTesteSalvo(Request $request, EmailCaixa $emailCaixa): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->assertEmpresa($emailCaixa, $empresaId);

        $validator = validator($request->all(), [
            'destino' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->with([
                    'smtp_send_error' => $validator->errors()->first(),
                    'smtp_send_id' => $emailCaixa->id,
                ]);
        }

        if (empty($emailCaixa->usuario)) {
            return back()
                ->withInput()
                ->with([
                    'smtp_send_error' => 'Informe o e-mail de login desta caixa para enviar o teste.',
                    'smtp_send_id' => $emailCaixa->id,
                ]);
        }

        try {
            $transport = $this->criarTransport($this->getConfigFromModel($emailCaixa));
            $mailer = new Mailer($transport);

            $email = (new Email())
                ->from($emailCaixa->usuario)
                ->to($request->input('destino'))
                ->subject('Teste SMTP')
                ->text('Este e um email de teste enviado pela configuracao SMTP.');

            $mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            return back()
                ->with([
                    'smtp_send_error' => 'Falha ao enviar: '.$e->getMessage(),
                    'smtp_send_id' => $emailCaixa->id,
                ]);
        } catch (\Throwable $e) {
            return back()
                ->with([
                    'smtp_send_error' => 'Erro inesperado ao enviar o email de teste.',
                    'smtp_send_id' => $emailCaixa->id,
                ]);
        }

        return back()
            ->with([
                'smtp_send_ok' => 'Email de teste enviado com sucesso.',
                'smtp_send_id' => $emailCaixa->id,
            ]);
    }

    private function assertEmpresa(EmailCaixa $emailCaixa, int $empresaId): void
    {
        if ((int) $emailCaixa->empresa_id !== $empresaId) {
            abort(403);
        }
    }

    private function getConfigFromModel(EmailCaixa $emailCaixa): array
    {
        return [
            'host' => $emailCaixa->host,
            'porta' => $emailCaixa->porta,
            'criptografia' => $emailCaixa->criptografia,
            'timeout' => $emailCaixa->timeout,
            'requer_autenticacao' => $emailCaixa->requer_autenticacao,
            'usuario' => $emailCaixa->usuario,
            'senha' => $emailCaixa->senha,
        ];
    }

    private function executarTesteSmtp(array $config): void
    {
        $transport = $this->criarTransport($config);

        $transport->start();
        $transport->stop();
    }

    private function criarTransport(array $config): EsmtpTransport
    {
        $criptografia = $config['criptografia'] === 'tls' ? 'starttls' : $config['criptografia'];
        $tls = $criptografia === 'ssl';

        $transport = new EsmtpTransport($config['host'], (int) $config['porta'], $tls);

        if (!empty($config['timeout'])) {
            $stream = $transport->getStream();
            if (method_exists($stream, 'setTimeout')) {
                $stream->setTimeout((float) $config['timeout']);
            }
        }

        if ($criptografia === 'starttls') {
            $transport->setAutoTls(true);
            $transport->setRequireTls(true);
        } else {
            $transport->setAutoTls(false);
            $transport->setRequireTls(false);
        }

        if (!empty($config['requer_autenticacao'])) {
            $transport->setUsername((string) ($config['usuario'] ?? ''));
            $transport->setPassword((string) ($config['senha'] ?? ''));
        }

        return $transport;
    }
}
