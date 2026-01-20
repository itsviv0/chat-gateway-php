<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\HealthController;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class HealthControllerTest extends TestCase
{
    private HealthController $controller;

    protected function setUp(): void
    {
        $this->controller = new HealthController();
    }

    public function testCheckReturns200Status(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = (new ResponseFactory())->createResponse();

        $result = $this->controller->check($request, $response);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function testCheckReturnsJsonContentType(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = (new ResponseFactory())->createResponse();

        $result = $this->controller->check($request, $response);

        $this->assertEquals('application/json', $result->getHeaderLine('Content-Type'));
    }

    public function testCheckReturnsValidJsonStructure(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = (new ResponseFactory())->createResponse();

        $result = $this->controller->check($request, $response);
        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('service', $data);
    }

    public function testCheckReturnsHealthyStatus(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = (new ResponseFactory())->createResponse();

        $result = $this->controller->check($request, $response);
        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertEquals('healthy', $data['status']);
    }

    public function testCheckReturnsValidTimestamp(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = (new ResponseFactory())->createResponse();

        $beforeTime = time();
        $result = $this->controller->check($request, $response);
        $afterTime = time();

        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertIsInt($data['timestamp']);
        $this->assertGreaterThanOrEqual($beforeTime, $data['timestamp']);
        $this->assertLessThanOrEqual($afterTime, $data['timestamp']);
    }

    public function testCheckReturnsServiceName(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/health');
        $response = (new ResponseFactory())->createResponse();

        $result = $this->controller->check($request, $response);
        $body = (string) $result->getBody();
        $data = json_decode($body, true);

        $this->assertIsString($data['service']);
        $this->assertNotEmpty($data['service']);
    }
}
