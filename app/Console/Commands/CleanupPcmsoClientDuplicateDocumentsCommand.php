<?php

namespace App\Console\Commands;

use App\Helpers\S3Helper;
use App\Models\Anexos;
use Illuminate\Console\Command;

class CleanupPcmsoClientDuplicateDocumentsCommand extends Command
{
    protected $signature = 'cleanup:pcmso-client-duplicates {--dry-run : Apenas listar os anexos duplicados sem remover}';

    protected $description = 'Remove anexos duplicados de PCMSO enviados pelo cliente, preservando o documento oficial da solicitacao.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $anexos = Anexos::query()
            ->with([
                'uploader.papel',
                'tarefa.servico',
                'tarefa.pcmsoSolicitacao',
            ])
            ->where('servico', 'PCMSO')
            ->whereHas('uploader', function ($query) {
                $query->whereHas('papel', function ($papelQuery) {
                    $papelQuery->whereRaw('LOWER(nome) = ?', ['cliente']);
                });
            })
            ->whereHas('tarefa.pcmsoSolicitacao', function ($query) {
                $query->where('pgr_origem', 'arquivo_cliente');
            })
            ->get()
            ->filter(function (Anexos $anexo) {
                $pcmso = $anexo->tarefa?->pcmsoSolicitacao;
                if (!$pcmso || blank($pcmso->pgr_arquivo_path) || blank($pcmso->pgr_arquivo_nome)) {
                    return false;
                }

                return (string) $anexo->nome_original === (string) $pcmso->pgr_arquivo_nome;
            })
            ->values();

        if ($anexos->isEmpty()) {
            $this->info('Nenhum anexo duplicado de PCMSO enviado pelo cliente foi encontrado.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d anexo(s) duplicado(s) de PCMSO enviado(s) pelo cliente %s.',
            $anexos->count(),
            $dryRun ? 'encontrado(s)' : 'serao removido(s)'
        ));

        $headers = ['Anexo ID', 'Tarefa', 'Cliente', 'Arquivo', 'Path anexo', 'Path oficial'];
        $rows = $anexos->map(function (Anexos $anexo) {
            $pcmso = $anexo->tarefa?->pcmsoSolicitacao;

            return [
                $anexo->id,
                $anexo->tarefa_id,
                $anexo->cliente_id,
                $anexo->nome_original,
                $anexo->path,
                $pcmso?->pgr_arquivo_path,
            ];
        })->all();

        $this->table($headers, $rows);

        if ($dryRun) {
            $this->comment('Dry-run finalizado. Nenhuma alteracao foi aplicada.');

            return self::SUCCESS;
        }

        $removidos = 0;

        foreach ($anexos as $anexo) {
            if ($anexo->path && S3Helper::exists($anexo->path)) {
                S3Helper::delete($anexo->path);
            }

            $anexo->delete();
            $removidos++;
        }

        $this->info(sprintf('%d anexo(s) duplicado(s) removido(s) com sucesso.', $removidos));

        return self::SUCCESS;
    }
}
