<?php

declare(strict_types=1);

namespace ready2order\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class AccountTest extends AbstractTestCase
{
    public function testGetCompanyInfo(): void
    {
        $info = $this->getApiClient()->get('company');

        self::assertArrayHasKey('company_name', $info);
    }
}
