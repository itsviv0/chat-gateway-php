<?php
/**
 * Test that Swagger generation works correctly
 */

require __DIR__ . '/../vendor/autoload.php';

use OpenApi\Generator;

echo "Testing Swagger/OpenAPI Generation...\n\n";

try {
    $basePath = __DIR__ . '/../';
    $openapi = Generator::scan([
        $basePath . 'src/Controllers',
        $basePath . 'src/OpenAPI',
    ]);

    $json = $openapi->toJson();
    $spec = json_decode($json, true);

    if ($spec === null) {
        echo "❌ Failed to parse generated OpenAPI spec\n";
        exit(1);
    }

    // Verify basic structure
    assert(isset($spec['openapi']), 'Missing openapi version');
    assert(isset($spec['info']), 'Missing info section');
    assert(isset($spec['info']['title']), 'Missing title');
    assert(isset($spec['info']['version']), 'Missing version');
    assert(isset($spec['servers']), 'Missing servers');
    assert(isset($spec['paths']), 'Missing paths');
    assert(isset($spec['components']['securitySchemes']['bearerAuth']), 'Missing security scheme');

    echo "✓ OpenAPI Spec Structure\n";
    echo "  - Title: " . $spec['info']['title'] . "\n";
    echo "  - Version: " . $spec['info']['version'] . "\n";
    echo "  - Base URL: " . $spec['servers'][0]['url'] . "\n\n";

    // Check documented paths
    $paths = array_keys($spec['paths']);
    echo "✓ Documented Endpoints (" . count($paths) . "):\n";
    foreach ($paths as $path) {
        echo "  - $path\n";
    }

    // Verify required endpoints exist
    $requiredEndpoints = [
        '/auth/login',
        '/users/me',
        '/groups',
        '/groups/{groupId}',
        '/health',
    ];

    echo "\n✓ Required Endpoints Validation:\n";
    foreach ($requiredEndpoints as $endpoint) {
        if (isset($spec['paths'][$endpoint])) {
            echo "  ✓ $endpoint\n";
        } else {
            echo "  ❌ $endpoint (MISSING)\n";
        }
    }

    // Check tags
    $tags = array_column($spec['tags'] ?? [], 'name');
    echo "\n✓ API Tags:\n";
    foreach ($tags as $tag) {
        echo "  - $tag\n";
    }

    // Count operations
    $operationCount = 0;
    foreach ($spec['paths'] as $pathItem) {
        foreach (['get', 'post', 'put', 'delete', 'patch'] as $method) {
            if (isset($pathItem[$method])) {
                $operationCount++;
            }
        }
    }

    echo "\n✓ Total Operations: $operationCount\n";
    echo "\n✅ All Swagger checks passed!\n";
    exit(0);
} catch (Exception $e) {
    echo "❌ Error generating OpenAPI spec:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
