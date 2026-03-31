<?php

namespace Tests\Unit;

use App\Services\FuncionarioArquivosZipService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class FuncionarioArquivosZipServiceTest extends TestCase
{
    public function test_obter_conteudo_arquivo_resolve_url_assinada_do_s3_para_path_interno(): void
    {
        Storage::fake('s3');
        Storage::disk('s3')->put('formed/tarefas-complementares/pcmso.pdf', 'conteudo-pcmso');

        $service = new FuncionarioArquivosZipService;

        $conteudo = $this->invocarMetodoPrivado(
            $service,
            'obterConteudoArquivo',
            'https://programador-de-apps-bucket-virginia.s3.amazonaws.com/formed/tarefas-complementares/pcmso.pdf?X-Amz-Algorithm=AWS4-HMAC-SHA256'
        );

        $this->assertSame('conteudo-pcmso', $conteudo);
    }

    public function test_obter_conteudo_arquivo_faz_fallback_para_download_http_quando_url_nao_eh_path_interno(): void
    {
        Http::fake([
            'https://files.example.com/*' => Http::response('conteudo-remoto', 200),
        ]);

        $service = new FuncionarioArquivosZipService;

        $conteudo = $this->invocarMetodoPrivado(
            $service,
            'obterConteudoArquivo',
            'https://files.example.com/documentos/pgr-final.pdf'
        );

        $this->assertSame('conteudo-remoto', $conteudo);
    }

    private function invocarMetodoPrivado(object $instance, string $method, mixed ...$args): mixed
    {
        $reflection = new ReflectionMethod($instance, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($instance, ...$args);
    }
}
