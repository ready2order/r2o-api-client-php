<?php

// Runtime smoke test for the minimum supported PHP version (7.4).
// Uses only production dependencies: Guzzle's MockHandler stands in for the network.
// Usage: php php74-smoke.php <path to the consumer project's vendor/autoload.php>

declare(strict_types=1);

require $argv[1];

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use ready2order\Client;

$mock = new MockHandler([new Response(200, [], '{"company_name":"ACME"}')]);
$client = new Client('token', 'http://smoke.test/v1', new GuzzleClient(['handler' => $mock]));

if ($client->setTimeout(5)->setLanguage('de-DE')->getTimeout() !== 5) {
    fwrite(STDERR, "setTimeout/getTimeout failed\n");
    exit(1);
}

if ($client->get('company') !== ['company_name' => 'ACME']) {
    fwrite(STDERR, "get() returned unexpected data\n");
    exit(1);
}

echo 'OK on PHP ' . PHP_VERSION . "\n";
