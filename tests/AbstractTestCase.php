<?php

declare(strict_types=1);

namespace Tests;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use ready2order\Client;

abstract class AbstractTestCase extends TestCase
{
    protected Client $api;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable strict error reporting
        error_reporting(E_ALL);

        // Load .env file
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
    }

    protected function getApiClient(): Client
    {
        if (isset($this->api) === false) {
            $accountToken = $_ENV['R2O_API_TOKEN'];
            $endpoint = $_ENV['R2O_API_ENDPOINT'];

            if (empty($accountToken)) {
                $this->fail('No R2O_API_TOKEN set!');
            }

            if (empty($endpoint)) {
                $this->fail('No R2O_API_ENDPOINT set!');
            }

            // initialize API wrapper
            $this->api = new Client($accountToken);
            $this->api->setApiEndpoint($endpoint);
        }

        return $this->api;
    }
}
