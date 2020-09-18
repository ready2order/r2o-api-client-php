<?php

declare(strict_types=1);

namespace Tests;

use ready2order\Exceptions\ErrorResponseException;
use ready2order\Exceptions\ResourceNotFoundException;

/**
 * @internal
 * @coversNothing
 */
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

            $this->assertNotNull($data, 'No data in exception!');

            $this->assertTrue($data['error']);
            $this->assertEquals('errors.validation.failed', $data['code']);
        }

        $this->assertTrue($exceptionThrown);
    }

    /**
     * @dataProvider getHttpMethods
     */
    public function testCallInvalidEndpoint(string $method): void
    {
        $method = mb_strtolower($method);
        $exceptionThrown = false;

        $this->expectException(ResourceNotFoundException::class);
        $this->getApiClient()->{$method}('invalid-endpoint');

        $this->assertTrue($exceptionThrown);
    }

    public function getHttpMethods(): array
    {
        return [
            [
                'GET',
            ],
            [
                'POST',
            ],
            [
                'PUT',
            ],
            [
                'PATCH',
            ],
            [
                'DELETE',
            ],
        ];
    }
}
