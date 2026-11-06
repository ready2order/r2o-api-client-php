<?php

declare(strict_types=1);

namespace ready2order\Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use ready2order\Client;

/**
 * Base class for tests that talk to a live ready2order API.
 *
 * Needs R2O_API_TOKEN and R2O_API_ENDPOINT (env or .env); skips otherwise.
 */
abstract class AbstractTestCase extends TestCase
{
    protected Client $api;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable strict error reporting
        error_reporting(\E_ALL);

        // Load the .env file when present; live API tests skip cleanly when no token is configured.
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }

    protected function getApiClient(): Client
    {
        if (isset($this->api) === false) {
            // getenv() fallback: $_ENV is empty when php.ini variables_order lacks "E" (e.g. CI).
            $accountToken = $_ENV['R2O_API_TOKEN'] ?? getenv('R2O_API_TOKEN') ?: '';
            $endpoint = $_ENV['R2O_API_ENDPOINT'] ?? getenv('R2O_API_ENDPOINT') ?: '';

            if (empty($accountToken)) {
                self::markTestSkipped('No R2O_API_TOKEN set; skipping live API test.');
            }

            if (empty($endpoint)) {
                self::markTestSkipped('No R2O_API_ENDPOINT set; skipping live API test.');
            }

            // initialize API wrapper
            $this->api = new Client($accountToken, $endpoint);
        }

        return $this->api;
    }
}
