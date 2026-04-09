<?php

namespace App\Services;

use App\Models\EmailCaixa;
use Symfony\Component\Mime\Email;

class ImapSentMailService
{
    public function appendToSentIfConfigured(EmailCaixa $emailCaixa, Email $email): void
    {
        $config = $this->configFromModel($emailCaixa);

        if (empty($config['host']) || empty($config['porta']) || empty($config['usuario']) || empty($config['senha'])) {
            return;
        }

        $stream = $this->connect($config);

        try {
            $this->readGreeting($stream);
            $tag = 1;

            if (($config['criptografia'] ?? 'ssl') === 'starttls') {
                $this->sendCommand($stream, $tag++, 'STARTTLS');
                stream_socket_enable_crypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }

            $this->sendCommand(
                $stream,
                $tag++,
                'LOGIN ' . $this->quote($config['usuario']) . ' ' . $this->quote($config['senha'])
            );

            $raw = $email->toString();
            $folder = $this->quote($config['sent_folder']);
            fwrite($stream, 'A' . $tag . ' APPEND ' . $folder . ' {' . strlen($raw) . "}\r\n");

            $continuation = fgets($stream);
            if ($continuation === false || !str_starts_with($continuation, '+')) {
                throw new \RuntimeException('Servidor IMAP não aceitou APPEND na pasta Enviados.');
            }

            fwrite($stream, $raw . "\r\n");
            $this->assertTaggedOk($stream, 'A' . $tag);

            $this->sendCommand($stream, $tag + 1, 'LOGOUT');
        } finally {
            fclose($stream);
        }
    }

    private function configFromModel(EmailCaixa $emailCaixa): array
    {
        $smtpHost = trim((string) ($emailCaixa->host ?? ''));
        $imapHost = trim((string) ($emailCaixa->imap_host ?? ''));

        if ($imapHost === '' && $smtpHost !== '') {
            $imapHost = preg_replace('/^smtp\./i', 'imap.', $smtpHost) ?: $smtpHost;
        }

        return [
            'host' => $imapHost,
            'porta' => $emailCaixa->imap_porta ?: 993,
            'criptografia' => $emailCaixa->imap_criptografia ?: 'ssl',
            'usuario' => $emailCaixa->imap_usuario ?: $emailCaixa->usuario,
            'senha' => $emailCaixa->imap_senha ?: $emailCaixa->senha,
            'sent_folder' => $emailCaixa->imap_sent_folder ?: 'INBOX.Sent',
        ];
    }

    private function connect(array $config)
    {
        $prefix = ($config['criptografia'] ?? 'ssl') === 'ssl' ? 'ssl://' : '';
        $stream = @stream_socket_client(
            $prefix . $config['host'] . ':' . $config['porta'],
            $errno,
            $errstr,
            15
        );

        if (!$stream) {
            throw new \RuntimeException('Falha ao conectar ao IMAP: ' . $errstr);
        }

        stream_set_timeout($stream, 15);

        return $stream;
    }

    private function readGreeting($stream): void
    {
        $line = fgets($stream);
        if ($line === false || stripos($line, 'OK') === false) {
            throw new \RuntimeException('Servidor IMAP não respondeu corretamente.');
        }
    }

    private function sendCommand($stream, int $number, string $command): void
    {
        $tag = 'A' . $number;
        fwrite($stream, $tag . ' ' . $command . "\r\n");
        $this->assertTaggedOk($stream, $tag);
    }

    private function assertTaggedOk($stream, string $tag): void
    {
        while (($line = fgets($stream)) !== false) {
            if (str_starts_with($line, $tag . ' ')) {
                if (stripos($line, 'OK') !== false) {
                    return;
                }

                throw new \RuntimeException(trim($line));
            }
        }

        throw new \RuntimeException('Sem resposta final do servidor IMAP.');
    }

    private function quote(string $value): string
    {
        return '"' . addcslashes($value, "\\\"") . '"';
    }
}
