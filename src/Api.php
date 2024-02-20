<?php declare(strict_types=1);

namespace Supercharge\Cli;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Ratchet\Client\WebSocket;
use RuntimeException;
use Supercharge\Cli\Config\TokenStorage;
use function Ratchet\Client\connect;

class Api
{
    public Client $client;
    private string $token;

    /**
     * @throws JsonException
     */
    public function __construct()
    {
        $token = (new TokenStorage)->load();
        if (! $token) {
            throw new RuntimeException('No token found. Please run "supercharge login" first.');
        }
        $this->token = $token;

        $this->client = new Client([
            'base_uri' => $_SERVER['API_BASE_URL'],
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ]);
    }

    /**
     * @return array{response: ResponseInterface, body: mixed}
     * @throws GuzzleException
     * @throws JsonException
     */
    public function get(string $uri): array
    {
        $response = $this->client->get($uri);
        $responseBody = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return [
            'response' => $response,
            'body' => $responseBody,
        ];
    }

    /**
     * @param array<mixed> $data
     * @return array{response: ResponseInterface, body: mixed}
     * @throws GuzzleException
     * @throws JsonException
     */
    public function post(string $uri, array $data): array
    {
        $response = $this->client->post($uri, [
            'json' => $data,
        ]);
        $json = $response->getBody()->getContents();
        if ($json !== '') {
            $responseBody = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }

        return [
            'response' => $response,
            'body' => $responseBody ?? [],
        ];
    }

    public function connectWebsocket(string $channel, Closure $onMessage, Closure $onError): void
    {
        connect('wss://lbwjh1c2pe.execute-api.us-east-1.amazonaws.com/prod', headers: [
            'Authorization' => 'Bearer ' . $this->token,
        ])->then(
            function (WebSocket $connection) use ($channel, $onMessage) {
                $connection->on('message', function ($payload) use ($connection, $onMessage) {
                    $onMessage($payload, $connection);
                });

                $connection->send(json_encode([
                    'action' => 'subscribe',
                    'channels' => [$channel],
                ], JSON_THROW_ON_ERROR));
            },
            $onError,
        );
    }
}
