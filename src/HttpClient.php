<?php
declare(strict_types=1);

namespace App;

final class HttpClient
{
    /**
     * @return array{ok:bool, code:int, content_type:string, body:string, error:?string, url:string}
     */
    public function get(string $url, int $timeoutSeconds = 25): array
    {
        // Reintento corto para errores server-side / transitorios
        $attempts = 2;
        $last = null;

        for ($i = 1; $i <= $attempts; $i++) {
            $res = $this->doRequest($url, $timeoutSeconds);

            // Si OK, devolvemos
            if ($res['ok']) return $res;

            // Si error no es retryable, devolvemos ya
            $retryable = in_array($res['code'], [500, 502, 503, 504], true);
            if (!$retryable) return $res;

            $last = $res;

            // Backoff chiquito (200ms, 400ms)
            usleep(200_000 * $i);
        }

        return $last ?? [
            'ok' => false,
            'code' => 0,
            'content_type' => '',
            'body' => '',
            'error' => 'Unknown error',
            'url' => $url,
        ];
    }

    /**
     * @return array{ok:bool, code:int, content_type:string, body:string, error:?string, url:string}
     */
    private function doRequest(string $url, int $timeoutSeconds): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeoutSeconds,

            // IMPORTANT: forzar HTTP/1.1 (a veces HTTP/2 da problemas raros)
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

            // Aceptar gzip/deflate y auto-decode
            CURLOPT_ENCODING => '',

            // User-Agent realista
            CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',

            CURLOPT_HTTPHEADER => [
                'Accept: text/xml, application/xml;q=0.9, text/plain;q=0.8, */*;q=0.7',
                'Accept-Language: es-AR,es;q=0.9,en;q=0.7',
                'Connection: close',
            ],
        ]);

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $ct   = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($body === false) {
            return [
                'ok' => false,
                'code' => 0,
                'content_type' => $ct,
                'body' => '',
                'error' => $err ?: 'curl error',
                'url' => $url,
            ];
        }

        if ($code < 200 || $code >= 300) {
            return [
                'ok' => false,
                'code' => $code,
                'content_type' => $ct,
                'body' => (string)$body,
                'error' => 'HTTP ' . $code,
                'url' => $url,
            ];
        }

        return [
            'ok' => true,
            'code' => $code,
            'content_type' => $ct,
            'body' => (string)$body,
            'error' => null,
            'url' => $url,
        ];
    }
}
