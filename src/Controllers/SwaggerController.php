<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Generator;

class SwaggerController
{
    /**
     * Generate and serve OpenAPI specification
     */
    public function spec(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $basePath = __DIR__ . '/../';
        $openapi = Generator::scan([
            $basePath . 'Controllers',
            $basePath . 'OpenAPI',
        ]);

        $response->getBody()->write($openapi->toJson());

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Serve Swagger UI
     */
    public function ui(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
  <head>
    <title>Chat Gateway API - Swagger UI</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css">
    <style>
      html{
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
      }
      *,
      *:before,
      *:after{
        box-sizing: inherit;
      }
      body{
        margin:0;
        padding:0;
      }
    </style>
  </head>
  <body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js"></script>
    <script>
      window.onload = function() {
        const ui = SwaggerUIBundle({
          url: "/swagger.json",
          dom_id: '#swagger-ui',
          presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
          ],
          layout: "StandaloneLayout",
          deepLinking: true,
          onComplete: function() {
            console.log("Swagger UI loaded successfully")
          },
          onFailure: function(data) {
            console.error("Unable to load swagger spec", data)
          }
        })
        window.ui = ui
      }
    </script>
  </body>
</html>
HTML;

        $response->getBody()->write($html);

        return $response
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
}
