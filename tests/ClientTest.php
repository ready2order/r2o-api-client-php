<?php

declare(strict_types=1);

namespace ready2order\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ready2order\Exceptions\ErrorResponseException;

/**
 * @internal
 */
#[CoversNothing]
class ClientTest extends AbstractTestCase
{
    public function testFluentInterfaceChaining(): void
    {
        $client = $this->getApiClient();

        // Test that methods can be chained
        $result = $client
            ->setTimeout(15)
            ->setLanguage('en-US');

        self::assertSame($client, $result);
        self::assertSame(15, $client->getTimeout());

        // Verify the client still works after chaining
        $company = $client->get('company');
        self::assertArrayHasKey('company_name', $company);
    }

    #[DataProvider('languageDataProvider')]
    public function testSettingLanguage(string $language): void
    {
        $client = $this->getApiClient();
        $client->setLanguage($language);

        try {
            $client->put('products', []);
            self::fail('Expected ErrorResponseException');
        } catch (ErrorResponseException $e) {
            // Assert on the structured error, not the exact localized copy,
            // so a reworded API message does not break the suite.
            self::assertSame('errors.validation.failed', $e->getData()['code']);
        }
    }

    public static function languageDataProvider(): array
    {
        return [
            ['de-AT'],
            ['en-US'],
        ];
    }
}
