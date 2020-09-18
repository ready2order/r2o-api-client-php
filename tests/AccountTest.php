<?php

declare(strict_types=1);

namespace Tests;

/**
 * @internal
 * @coversNothing
 */
class AccountTest extends AbstractTestCase
{
    public function testGetCompanyInfo(): void
    {
        $info = $this->getApiClient()->get('company');

        $this->assertArrayHasKey('company_name', $info);
    }
}
