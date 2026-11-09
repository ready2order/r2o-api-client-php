<?php

declare(strict_types=1);

namespace ready2order\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ready2order\Exceptions\ErrorResponseException;
use ready2order\Exceptions\ResourceNotFoundException;

/**
 * @internal
 */
#[CoversNothing]
class ErrorHandlingTest extends AbstractTestCase
{
    public function testPutRequestWithoutPayload(): void
    {
        $exceptionThrown = false;

        try {
            $this->getApiClient()->put('productgroups');
        } catch (ErrorResponseException $e) {
            $exceptionThrown = true;
            $data = $e->getData();

            self::assertNotNull($data, 'No data in exception!');

            self::assertTrue($data['error']);
            self::assertEquals('errors.validation.failed', $data['code']);
        }

        self::assertTrue($exceptionThrown);
    }

    #[DataProvider('getHttpMethods')]
    public function testCallInvalidEndpoint(string $method): void
    {
        self::expectException(ResourceNotFoundException::class);
        $this->getApiClient()->{$method}('invalid-endpoint');
    }

    public static function getHttpMethods(): array
    {
        return [
            ['get'],
            ['post'],
            ['put'],
            ['patch'],
            ['delete'],
        ];
    }
}
