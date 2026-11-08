<?php

declare(strict_types=1);

namespace ready2order\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
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

    public function testSettingLanguage(): void
    {
        $client = $this->getApiClient();
        $messages = [];

        foreach (['de-AT', 'en-US'] as $language) {
            $client->setLanguage($language);

            try {
                $client->put('products', []);
                self::fail('Expected ErrorResponseException');
            } catch (ErrorResponseException $e) {
                // The code is language-independent; the message must be localized.
                self::assertSame('errors.validation.failed', $e->getData()['code']);
                $messages[$language] = $e->getMessage();
            }
        }

        // Compare across languages instead of pinning exact copy, so a reworded
        // API message does not break the suite but a no-op setLanguage() does.
        self::assertNotSame($messages['de-AT'], $messages['en-US']);
    }
}
