<?php

declare(strict_types=1);

namespace ready2order\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ready2order\Client;
use ready2order\Exceptions\ConnectionException;
use ready2order\Exceptions\ErrorResponseException;
use ready2order\Exceptions\InvalidResponseException;
use ready2order\Exceptions\ResourceNotFoundException;
use stdClass;

/**
 * @internal
 */
#[CoversClass(Client::class)]
class ClientUnitTest extends TestCase
{
    private const TOKEN = 'test-token';
    private const ENDPOINT = 'http://unit.test/v1';

    public function testSuccessReturnsDecodedJson(): void
    {
        $mock = new MockHandler([new Response(200, [], '{"company_name":"ACME"}')]);
        [$client] = $this->clientWith($mock);

        self::assertSame(['company_name' => 'ACME'], $client->get('company'));
    }

    public function testFluentInterfaceReturnsSelf(): void
    {
        $client = new Client(self::TOKEN, self::ENDPOINT);

        self::assertSame($client, $client->setTimeout(30));
        self::assertSame($client, $client->setLanguage('de-DE'));
        self::assertSame(30, $client->getTimeout());
    }

    public function testDefaultTimeoutIsApplied(): void
    {
        [$client, $sink] = $this->clientCapturingOptions();
        $client->get('company');

        self::assertSame(10, $sink->options['timeout']);
    }

    public function testSetTimeoutIsApplied(): void
    {
        [$client, $sink] = $this->clientCapturingOptions();
        $client->setTimeout(30);
        $client->get('company');

        self::assertSame(30, $sink->options['timeout']);
    }

    public function testPerRequestTimeoutOverridesDefault(): void
    {
        [$client, $sink] = $this->clientCapturingOptions();
        $client->setTimeout(30);
        $client->get('company', [], 5);

        self::assertSame(5, $sink->options['timeout']);
    }

    public function testClientErrorThrowsErrorResponseException(): void
    {
        $mock = new MockHandler([
            new ClientException('', $this->request(), new Response(400, [], '{"error":true,"code":"errors.validation.failed","msg":"Bad field"}')),
        ]);
        [$client] = $this->clientWith($mock);

        try {
            $client->put('products', []);
            self::fail('Expected ErrorResponseException');
        } catch (ErrorResponseException $e) {
            self::assertSame('Bad field', $e->getMessage());
            self::assertSame(['error' => true, 'code' => 'errors.validation.failed', 'msg' => 'Bad field'], $e->getData());
        }
    }

    public function testNotFoundThrowsResourceNotFoundException(): void
    {
        $mock = new MockHandler([
            new ClientException('', $this->request(), new Response(404, [], '{"error":true,"code":"errors.not_found","msg":"Gone"}')),
        ]);
        [$client] = $this->clientWith($mock);

        try {
            $client->get('products/999');
            self::fail('Expected ResourceNotFoundException');
        } catch (ResourceNotFoundException $e) {
            self::assertSame('Gone', $e->getMessage());
        }
    }

    public function testServerErrorThrowsErrorResponseException(): void
    {
        $mock = new MockHandler([
            new ServerException('', $this->request(), new Response(500, [], '{"error":true,"code":"errors.server.failed","msg":"Boom"}')),
        ]);
        [$client] = $this->clientWith($mock);

        try {
            $client->get('products');
            self::fail('Expected ErrorResponseException');
        } catch (ErrorResponseException $e) {
            self::assertSame('Boom', $e->getMessage());
            self::assertSame('errors.server.failed', $e->getData()['code']);
        }
    }

    public function testNonJsonErrorThrowsErrorResponseExceptionWithRawBody(): void
    {
        $mock = new MockHandler([
            new ClientException('', $this->request(), new Response(400, ['Content-Type' => 'text/html'], '<html><body>Bad Gateway</body></html>')),
        ]);
        [$client] = $this->clientWith($mock);

        try {
            $client->get('products');
            self::fail('Expected ErrorResponseException');
        } catch (ErrorResponseException $e) {
            self::assertStringContainsString('could not be JSON-decoded', $e->getMessage());
            self::assertStringContainsString('Bad Gateway', $e->getMessage());
            self::assertNull($e->getData());
        }
    }

    public function testConnectionFailureThrowsConnectionException(): void
    {
        $mock = new MockHandler([
            new ConnectException('Could not resolve host: unit.test', $this->request()),
        ]);
        [$client] = $this->clientWith($mock);

        try {
            $client->get('products');
            self::fail('Expected ConnectionException');
        } catch (ConnectionException $e) {
            self::assertStringContainsString('Could not resolve host', $e->getMessage());
            self::assertInstanceOf(ConnectException::class, $e->getPrevious());
        }
    }

    public function testNonJsonSuccessThrowsInvalidResponseException(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'text/plain'], 'not-json'),
        ]);
        [$client] = $this->clientWith($mock);

        try {
            $client->get('products');
            self::fail('Expected InvalidResponseException');
        } catch (InvalidResponseException $e) {
            self::assertStringContainsString('GET', $e->getMessage());
            self::assertStringContainsString('not-json', $e->getMessage());
        }
    }

    public function testAcceptLanguageHeaderIsSent(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        [$client] = $this->clientWith($mock);
        $client->setLanguage('de-DE');
        $client->get('company');

        self::assertSame('de-DE', $mock->getLastRequest()->getHeaderLine('Accept-Language'));
    }

    public function testAuthorizationHeaderIsSent(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        [$client] = $this->clientWith($mock);
        $client->get('company');

        self::assertSame(self::TOKEN, $mock->getLastRequest()->getHeaderLine('Authorization'));
    }

    /**
     * Build a client around a MockHandler (queued responses/exceptions, no network).
     *
     * @return array{0: Client, 1: MockHandler}
     */
    private function clientWith(MockHandler $mock): array
    {
        $guzzle = new GuzzleClient(['handler' => $mock]);

        return [new Client(self::TOKEN, self::ENDPOINT, $guzzle), $mock];
    }

    private function request(): Request
    {
        return new Request('GET', self::ENDPOINT . '/products');
    }

    /**
     * Capture the Guzzle request options a call is made with.
     *
     * @return array{0: Client, 1: stdClass} [client, sink holding the captured options]
     */
    private function clientCapturingOptions(): array
    {
        $sink = new stdClass();
        $sink->options = [];
        $handler = static function (RequestInterface $request, array $options) use ($sink): PromiseInterface {
            $sink->options = $options;

            return new FulfilledPromise(new Response(200, [], '[]'));
        };
        // @phpstan-ignore argument.type (Guzzle's PromiseInterface template is not covariant)
        $guzzle = new GuzzleClient(['handler' => $handler]);

        return [new Client(self::TOKEN, self::ENDPOINT, $guzzle), $sink];
    }
}
