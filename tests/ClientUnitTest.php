<?php

declare(strict_types=1);

namespace ready2order\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use ready2order\Client;
use ready2order\Exceptions\ConnectionException;
use ready2order\Exceptions\ErrorResponseException;
use ready2order\Exceptions\InvalidResponseException;
use ready2order\Exceptions\Ready2OrderException;
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

    #[DataProvider('transferFailureProvider')]
    public function testTransferFailureThrowsConnectionException(TransferException $failure): void
    {
        $mock = new MockHandler([$failure]);
        [$client] = $this->clientWith($mock);

        try {
            $client->get('products');
            self::fail('Expected ConnectionException');
        } catch (ConnectionException $e) {
            self::assertStringContainsString('GET products', $e->getMessage());
            self::assertStringContainsString('Could not resolve host', $e->getMessage());
            self::assertSame($failure, $e->getPrevious());
        }
    }

    /**
     * Failures without an HTTP error response (Guzzle's TransferException family).
     *
     * @return array<string, array{0: TransferException}>
     */
    public static function transferFailureProvider(): array
    {
        $request = new Request('GET', self::ENDPOINT . '/products');

        return [
            'connect failure' => [new ConnectException('Could not resolve host: unit.test', $request)],
            'request failure without response' => [new RequestException('Could not resolve host: unit.test', $request)],
            'redirect loop' => [new TooManyRedirectsException('Could not resolve host: unit.test', $request, new Response(302))],
        ];
    }

    #[DataProvider('libraryExceptionProvider')]
    public function testLibraryExceptionsShareBaseClass(string $class): void
    {
        self::assertTrue(is_subclass_of($class, Ready2OrderException::class));
    }

    /**
     * @return array<string, array{0: class-string}>
     */
    public static function libraryExceptionProvider(): array
    {
        return [
            'connection' => [ConnectionException::class],
            'error response' => [ErrorResponseException::class],
            'invalid response' => [InvalidResponseException::class],
            'not found' => [ResourceNotFoundException::class],
        ];
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

    /**
     * @param array<string, mixed> $args
     */
    #[DataProvider('verbProvider')]
    public function testVerbSendsMethodUrlAndArgs(string $verb, string $method, bool $inQuery, array $args): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        [$client] = $this->clientWith($mock);
        $client->{$verb}('products', $args);

        $request = $mock->getLastRequest();
        self::assertSame($method, $request->getMethod());
        self::assertSame(self::ENDPOINT . '/products', (string) $request->getUri()->withQuery(''));
        if ($inQuery) {
            self::assertSame('a=1', $request->getUri()->getQuery());
            self::assertSame('', (string) $request->getBody());
        } else {
            self::assertSame('', $request->getUri()->getQuery());
            self::assertSame('a=1', (string) $request->getBody());
        }
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool, 3: array<string, int>}>
     */
    public static function verbProvider(): array
    {
        return [
            'get' => ['get', 'GET', true, ['a' => 1]],
            'post' => ['post', 'POST', false, ['a' => 1]],
            'put' => ['put', 'PUT', false, ['a' => 1]],
            'patch' => ['patch', 'PATCH', false, ['a' => 1]],
            'delete' => ['delete', 'DELETE', false, ['a' => 1]],
        ];
    }

    public function testUrlJoinNormalizesSlashes(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}'), new Response(200, [], '{}')]);
        $client = new Client(self::TOKEN, self::ENDPOINT . '/', new GuzzleClient(['handler' => $mock]));

        $uris = [];
        foreach (['products', '/products'] as $path) {
            $client->get($path);
            $uris[] = (string) $mock->getLastRequest()->getUri();
        }

        self::assertSame([self::ENDPOINT . '/products', self::ENDPOINT . '/products'], $uris);
    }

    public function testDefaultAcceptLanguageIsEnUs(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        [$client] = $this->clientWith($mock);
        $client->get('company');

        self::assertSame('en-US', $mock->getLastRequest()->getHeaderLine('Accept-Language'));
    }

    public function testDefaultEndpointIsProduction(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        (new Client(self::TOKEN, null, new GuzzleClient(['handler' => $mock])))->get('company');

        self::assertSame('https://api.ready2order.com/v1/company', (string) $mock->getLastRequest()->getUri());
    }

    public function testErrorResponsesStillThrowWhenInjectedClientDisablesHttpErrors(): void
    {
        $mock = new MockHandler([new Response(500, [], '{"error":true,"code":"x","msg":"Boom"}')]);
        // The http_errors middleware lives in the default stack, a bare MockHandler would skip it.
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);
        $client = new Client(self::TOKEN, self::ENDPOINT, $guzzle);

        $this->expectException(ErrorResponseException::class);
        $client->get('products');
    }

    public function testErrorWithoutMsgReportsStatusAndBody(): void
    {
        $mock = new MockHandler([
            new ClientException('', $this->request(), new Response(401, [], '{"message":"Unauthenticated"}')),
        ]);
        [$client] = $this->clientWith($mock);

        try {
            $client->get('products');
            self::fail('Expected ErrorResponseException');
        } catch (ErrorResponseException $e) {
            self::assertStringContainsString('HTTP 401', $e->getMessage());
            self::assertStringContainsString('Unauthenticated', $e->getMessage());
            self::assertSame(['message' => 'Unauthenticated'], $e->getData());
        }
    }

    public function testArrayMsgIsEncodedNotCast(): void
    {
        $mock = new MockHandler([
            new ClientException('', $this->request(), new Response(400, [], '{"error":true,"msg":{"field":"bad"}}')),
        ]);
        [$client] = $this->clientWith($mock);

        try {
            $client->get('products');
            self::fail('Expected ErrorResponseException');
        } catch (ErrorResponseException $e) {
            self::assertSame('{"field":"bad"}', $e->getMessage());
        }
    }

    public function testNotFoundWithHtmlBodyThrowsResourceNotFoundException(): void
    {
        $mock = new MockHandler([
            new ClientException('', $this->request(), new Response(404, [], '<html>nope</html>')),
        ]);
        [$client] = $this->clientWith($mock);

        $this->expectException(ResourceNotFoundException::class);
        $client->get('products/1');
    }

    public function testEmptySuccessBodyThrowsInvalidResponseException(): void
    {
        $mock = new MockHandler([new Response(200, [], '')]);
        [$client] = $this->clientWith($mock);

        $this->expectException(InvalidResponseException::class);
        $client->get('products');
    }

    public function testLongBodiesAreTruncatedMultibyteSafe(): void
    {
        $mock = new MockHandler([new Response(200, [], str_repeat('ä', 600))]);
        [$client] = $this->clientWith($mock);

        try {
            $client->get('products');
            self::fail('Expected InvalidResponseException');
        } catch (InvalidResponseException $e) {
            self::assertStringContainsString(str_repeat('ä', 500) . ' …', $e->getMessage());
            self::assertStringNotContainsString(str_repeat('ä', 501), $e->getMessage());
            self::assertTrue(mb_check_encoding($e->getMessage(), 'UTF-8'));
        }
    }

    public function testAuthorizationHeaderIsSent(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        [$client] = $this->clientWith($mock);
        $client->get('company');

        self::assertSame(self::TOKEN, $mock->getLastRequest()->getHeaderLine('Authorization'));
    }

    public function testInjectedClientKeepsItsOwnDefaultHeaders(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        $guzzle = new GuzzleClient(['handler' => $mock, 'headers' => ['User-Agent' => 'custom-agent/1.0']]);
        (new Client(self::TOKEN, self::ENDPOINT, $guzzle))->get('company');

        self::assertSame('custom-agent/1.0', $mock->getLastRequest()->getHeaderLine('User-Agent'));
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
