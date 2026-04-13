<?php

namespace App\Services;

use App\Models\WhatsappInstancia;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WhatsappEvolutionService
{
    protected function client(WhatsappInstancia $instancia): PendingRequest
    {
        $apiKey = (string) config('services.evolution.api_key');
        if ($apiKey === '') {
            $apiKey = (string) $instancia->api_key;
        }

        if ($apiKey === '') {
            throw new InvalidArgumentException('Informe a API Key da Evolution para continuar.');
        }

        return Http::withHeaders([
            'apikey' => $apiKey,
            'Accept' => 'application/json',
        ])->timeout(25);
    }

    protected function baseUrl(WhatsappInstancia $instancia): string
    {
        $baseUrl = (string) config('services.evolution.base_url');
        if ($baseUrl === '') {
            $baseUrl = (string) $instancia->base_url;
        }
        $baseUrl = rtrim($baseUrl, '/');

        if ($baseUrl === '') {
            throw new InvalidArgumentException('Informe a URL base da Evolution para continuar.');
        }

        return $baseUrl;
    }

    public function createInstance(string $instanceName, string $number, WhatsappInstancia $instancia): array
    {
        $payload = [
            'instanceName' => $instanceName,
            'name' => $instanceName,
            'channel' => $instancia->channel,
            'token' => $instancia->token,
            'number' => $number,
        ];

        Log::info('WA createInstance request', [
            'empresa_id' => $instancia->empresa_id,
            'base_url' => $this->baseUrl($instancia),
            'instance_name' => $instanceName,
            'channel' => $instancia->channel,
            'token_present' => filled($instancia->token),
            'number' => $number,
        ]);

        $response = $this->client($instancia)
            ->post($this->baseUrl($instancia).'/instance/create', $payload);

        if (!$response->successful()) {
            Log::warning('WA createInstance failed', [
                'empresa_id' => $instancia->empresa_id,
                'status' => $response->status(),
                'body' => $response->body(),
                'instance_name' => $instanceName,
            ]);
        }

        $json = $response->throw()->json();

        return [
            'state' => $this->mapState($json),
            'raw' => $json,
            'name' => $instanceName,
            'number' => $number,
        ];
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function connect(string $instanceName, WhatsappInstancia $instancia, ?string $number = null): array
    {
        $query = [];
        if (!empty($number)) {
            $query['number'] = $number;
        }

        Log::info('WA connect request', [
            'empresa_id' => $instancia->empresa_id,
            'base_url' => $this->baseUrl($instancia),
            'instance_name' => $instanceName,
            'number' => $number,
        ]);

        $response = $this->client($instancia)
            ->get($this->baseUrl($instancia)."/instance/connect/{$instanceName}", $query);

        if (!$response->successful()) {
            Log::warning('WA connect failed', [
                'empresa_id' => $instancia->empresa_id,
                'status' => $response->status(),
                'body' => $response->body(),
                'instance_name' => $instanceName,
            ]);
        }

        $json = $response->throw()->json();

        $pairingCode = data_get($json, 'pairingCode')
            ?? data_get($json, 'code')
            ?? data_get($json, 'pairing.code')
            ?? data_get($json, 'pairingCode.code')
            ?? data_get($json, 'connection.pairingCode');

        $base64 = data_get($json, 'base64')
            ?? data_get($json, 'qrcode.base64')
            ?? data_get($json, 'qr.base64')
            ?? data_get($json, 'data.base64')
            ?? data_get($json, 'data.qrcode.base64')
            ?? data_get($json, 'qrcode')
            ?? data_get($json, 'qr')
            ?? data_get($json, 'data.qr');

        if (is_string($base64) && Str::startsWith($base64, 'data:image')) {
            $base64 = preg_replace('#^data:image/[^;]+;base64,#', '', $base64) ?: $base64;
        }

        Log::info('WA connect response', [
            'empresa_id' => $instancia->empresa_id,
            'instance_name' => $instanceName,
            'has_pairing_code' => filled($pairingCode),
            'has_base64' => filled($base64),
            'response_keys' => array_keys($json),
        ]);

        return [
            'pairingCode' => $pairingCode,
            'code' => data_get($json, 'code'),
            'base64' => $base64,
            'raw' => $json,
        ];
    }

    public function status(string $instanceName, WhatsappInstancia $instancia): string
    {
        $response = $this->client($instancia)
            ->get($this->baseUrl($instancia)."/instance/connectionState/{$instanceName}");

        if (!$response->successful()) {
            Log::warning('WA status failed', [
                'empresa_id' => $instancia->empresa_id,
                'status' => $response->status(),
                'body' => $response->body(),
                'instance_name' => $instanceName,
            ]);
        }

        $json = $response->throw()->json();

        return $this->mapState($json);
    }

    public function restart(string $instanceName, WhatsappInstancia $instancia): array
    {
        Log::info('WA restart request', [
            'empresa_id' => $instancia->empresa_id,
            'base_url' => $this->baseUrl($instancia),
            'instance_name' => $instanceName,
        ]);

        $response = $this->client($instancia)
            ->put($this->baseUrl($instancia)."/instance/restart/{$instanceName}");

        if (!$response->successful()) {
            Log::warning('WA restart failed', [
                'empresa_id' => $instancia->empresa_id,
                'status' => $response->status(),
                'body' => $response->body(),
                'instance_name' => $instanceName,
            ]);
        }

        return $response->throw()->json() ?? [];
    }

    public function logout(string $instanceName, WhatsappInstancia $instancia): array
    {
        Log::info('WA logout request', [
            'empresa_id' => $instancia->empresa_id,
            'base_url' => $this->baseUrl($instancia),
            'instance_name' => $instanceName,
        ]);

        $response = $this->client($instancia)
            ->delete($this->baseUrl($instancia)."/instance/logout/{$instanceName}");

        if (!$response->successful()) {
            Log::warning('WA logout failed', [
                'empresa_id' => $instancia->empresa_id,
                'status' => $response->status(),
                'body' => $response->body(),
                'instance_name' => $instanceName,
            ]);
        }

        return $response->throw()->json() ?? [];
    }

    public function sendButtons(
        WhatsappInstancia $instancia,
        string $instanceName,
        string $number,
        string $title,
        string $description,
        ?string $footer,
        array $buttons
    ): array {
        return $this->client($instancia)
            ->post($this->baseUrl($instancia)."/message/sendButtons/{$instanceName}", [
                'number' => $number,
                'title' => $title,
                'description' => $description,
                'footer' => $footer ?? '',
                'buttons' => array_values($buttons),
            ])
            ->throw()
            ->json() ?? [];
    }

    public function sendText(
        WhatsappInstancia $instancia,
        string $instanceName,
        string $number,
        string $text,
        array $options = []
    ): array {
        $payload = array_filter(array_merge([
            'number' => $number,
            'text' => $text,
        ], $options), fn ($value) => !is_null($value));

        try {
            $response = $this->client($instancia)
                ->asJson()
                ->post($this->baseUrl($instancia)."/message/sendText/{$instanceName}", $payload);

        if ($response->successful()) {
            $json = $response->json() ?? [];

            $payload = is_array($json)
                ? $json
                : [];

            $payload['_meta'] = ['ok' => true, 'http_status' => $response->status()];
            $payload['ok'] = true;

            return $payload;
        }

            Log::warning('WA sendText falhou', [
                'empresa_id' => $instancia->empresa_id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'ok' => false,
                'status' => $response->status(),
                'body' => $response->body(),
            ];
        } catch (ConnectionException $e) {
            Log::notice('WA sendText timeout (sem confirmacao)', [
                'empresa_id' => $instancia->empresa_id,
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'ok' => false,
                'timeout' => true,
                'status' => null,
                'note' => 'Provider timed out',
            ];
        }
    }

    protected function mapState(array $json): string
    {
        $raw = $json['instance']['state'] ?? $json['state'] ?? null;
        $raw = is_string($raw) ? strtolower($raw) : null;

        return match ($raw) {
            'open' => 'open',
            'connecting' => 'connecting',
            'closed' => 'closed',
            default => $raw ?: 'closed',
        };
    }
}
