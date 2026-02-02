<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Funcao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FuncoesController extends Controller
{
    private function autorizarAcesso(Request $request): void
    {
        $user = $request->user();

        if (!$user || !$user->hasPapel(['Comercial', 'Master'])) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->autorizarAcesso($request);

        $empresaId = $request->user()->empresa_id;
        $q = $request->string('q')->toString();
        $status = $request->string('status')->toString(); // ativos | inativos | ''

        $funcoes = Funcao::query()
            ->where('empresa_id', $empresaId)
            ->withCount(['funcionarios', 'gheFuncoes'])
            ->when($q !== '', fn($query) => $query->where('nome', 'like', "%{$q}%"))
            ->when($status === 'ativos', fn($query) => $query->where('ativo', true))
            ->when($status === 'inativos', fn($query) => $query->where('ativo', false))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        $funcoesAutocomplete = $funcoes->getCollection()
            ->pluck('nome')
            ->filter()
            ->unique()
            ->values();

        return view('comercial.funcoes.index', [
            'funcoes' => $funcoes,
            'q' => $q,
            'status' => $status,
            'funcoesAutocomplete' => $funcoesAutocomplete,
        ]);
    }

    public function store(Request $request)
    {
        $this->autorizarAcesso($request);

        $empresaId = $request->user()->empresa_id;

        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('funcoes', 'nome')->where('empresa_id', $empresaId),
            ],
            'cbo' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $data['empresa_id'] = $empresaId;
        $data['ativo'] = $request->boolean('ativo');

        Funcao::create($data);

        return back()->with('ok', 'Função cadastrada com sucesso.');
    }

    public function update(Request $request, Funcao $funcao)
    {
        $this->autorizarAcesso($request);

        $empresaId = $request->user()->empresa_id;
        abort_if($funcao->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('funcoes', 'nome')->where('empresa_id', $empresaId)->ignore($funcao->id),
            ],
            'cbo' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $data['ativo'] = $request->boolean('ativo');

        $funcao->update($data);

        return back()->with('ok', 'Função atualizada com sucesso.');
    }

    public function destroy(Request $request, Funcao $funcao)
    {
        $this->autorizarAcesso($request);

        $empresaId = $request->user()->empresa_id;
        abort_if($funcao->empresa_id !== $empresaId, 403);

        $temVinculo = $funcao->funcionarios()->exists()
            || $funcao->gheFuncoes()->exists();

        if ($temVinculo) {
            $funcao->update(['ativo' => false]);

            return back()->with('ok', 'Função possui vínculos e foi inativada.');
        }

        $funcao->delete();

        return back()->with('ok', 'Função excluída com sucesso.');
    }

    public function import(Request $request)
    {
        $this->autorizarAcesso($request);

        $data = $request->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx,csv,txt'],
        ]);

        $empresaId = $request->user()->empresa_id;

        $extensao = strtolower((string) $data['arquivo']->getClientOriginalExtension());
        if ($extensao === 'csv' || $extensao === 'txt') {
            [$nomes, $erros] = $this->lerCsvFuncoes($data['arquivo']->getRealPath());
        } else {
            [$nomes, $erros] = $this->lerPlanilhaFuncoes($data['arquivo']->getRealPath());
        }

        if ($erros) {
            return back()->with('erro', $erros);
        }

        $existentes = Funcao::query()
            ->where('empresa_id', $empresaId)
            ->pluck('nome')
            ->map(fn($nome) => mb_strtolower(trim((string) $nome)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existentesSet = array_fill_keys($existentes, true);
        $vistos = [];
        $importadas = 0;
        $ignoradas = 0;
        $nomesIgnorados = [];

        foreach ($nomes as $nome) {
            $normalizado = mb_strtolower(trim($nome));
            if ($normalizado === '') {
                $ignoradas++;
                $nomesIgnorados[] = $nome;
                continue;
            }

            if (isset($existentesSet[$normalizado]) || isset($vistos[$normalizado])) {
                $ignoradas++;
                $nomesIgnorados[] = $nome;
                continue;
            }

            Funcao::create([
                'empresa_id' => $empresaId,
                'nome' => $nome,
                'cbo' => null,
                'descricao' => null,
                'ativo' => true,
            ]);

            $vistos[$normalizado] = true;
            $importadas++;
        }

        $nomesIgnorados = collect($nomesIgnorados)
            ->map(fn($nome) => trim((string) $nome))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return back()
            ->with('ok', "Importação finalizada: {$importadas} importada(s), {$ignoradas} ignorada(s).")
            ->with('ignored', $nomesIgnorados);
    }

    private function lerPlanilhaFuncoes(string $caminho): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return [[], 'Não foi possível ler a planilha (ZipArchive indisponível).'];
        }

        $zip = new \ZipArchive();
        if ($zip->open($caminho) !== true) {
            return [[], 'Não foi possível abrir o arquivo XLSX.'];
        }

        $shared = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            $sharedDoc = @simplexml_load_string($sharedXml);
            if ($sharedDoc) {
                foreach ($sharedDoc->si as $si) {
                    $textos = [];
                    foreach ($si->t as $t) {
                        $textos[] = (string) $t;
                    }
                    foreach ($si->r as $r) {
                        $textos[] = (string) $r->t;
                    }
                    $shared[] = implode('', $textos);
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if (!$sheetXml) {
            $zip->close();
            return [[], 'Planilha inválida: aba principal não encontrada.'];
        }

        $sheetDoc = @simplexml_load_string($sheetXml);
        if (!$sheetDoc) {
            $zip->close();
            return [[], 'Não foi possível ler os dados da planilha.'];
        }

        $nomes = [];
        foreach ($sheetDoc->sheetData->row as $row) {
            $valor = null;
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                if (str_starts_with($ref, 'A')) {
                    $tipo = (string) $cell['t'];
                    if ($tipo === 'inlineStr') {
                        $valor = (string) ($cell->is->t ?? '');
                    } else {
                        $v = (string) ($cell->v ?? '');
                        if ($tipo === 's') {
                            $valor = $shared[(int) $v] ?? '';
                        } else {
                            $valor = $v;
                        }
                    }
                    break;
                }
            }

            $valor = trim((string) $valor);
            if ($valor !== '') {
                $nomes[] = $valor;
            } else {
                $nomes[] = '';
            }
        }

        $zip->close();

        return [$nomes, null];
    }

    private function lerCsvFuncoes(string $caminho): array
    {
        if (!is_file($caminho)) {
            return [[], 'Não foi possível abrir o arquivo CSV.'];
        }

        $conteudo = file_get_contents($caminho);
        if ($conteudo === false) {
            return [[], 'Não foi possível ler o arquivo CSV.'];
        }

        $linhas = preg_split('/\r\n|\r|\n/', $conteudo);
        $nomes = [];

        foreach ($linhas as $linha) {
            if (trim($linha) === '') {
                $nomes[] = '';
                continue;
            }
            $colunas = str_getcsv($linha);
            $nomes[] = $colunas[0] ?? '';
        }

        return [$nomes, null];
    }
}
