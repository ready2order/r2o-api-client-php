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
}
