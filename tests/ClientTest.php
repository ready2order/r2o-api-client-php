<?php

declare(strict_types=1);

namespace ready2order\Tests;

/**
 * @internal
 * @coversNothing
 */
class ClientTest extends AbstractTestCase
{
    public function testSettingTimeout(): void
    {
        $client = $this->getApiClient();
        $client->setTimeout(5);

        $this->assertSame(5, $client->getTimeout());
    }

    /**
     * @dataProvider languageDataProvider
     *
     * @param mixed $expectedError
     */
    public function testSettingLanguage(string $language, $expectedError): void
    {
        $client = $this->getApiClient();
        $client->setLanguage($language);
        $this->expectExceptionMessage($expectedError);
        $client->put('products', []);
    }

    public function languageDataProvider()
    {
        return [
            [
                'de-AT',
                <<<'EOT'
                product name muss ausgefüllt sein.
                product price muss ausgefüllt sein.
                product vat muss ausgefüllt sein.
                EOT
            ],
            [
                'en-US',
                <<<'EOT'
                The product name field is required.
                The product price field is required.
                The product vat field is required.
                EOT
            ],
        ];
    }
}
