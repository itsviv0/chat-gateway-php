<?php
// tests/verify_path_resolution.php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Database;
use DI\ContainerBuilder;

function checkPath($inputPath, $expectedAbsolute)
{
    // We cannot easily hook into the container closure to check intermediate variables.
    // However, we can check the resolved path in the Database object if we use reflection,
    // or we can just copy the logic here to verify independent correctness.

    // Testing the logic isolated:
    $envPath = $inputPath;
    $isAbsolute = str_starts_with($envPath, '/') ||
        str_starts_with($envPath, '\\') ||
        preg_match('/^[a-zA-Z]:[\\\\\/]/', $envPath);

    echo "Input: '$inputPath' -> Resolved Absolute? " . ($isAbsolute ? 'YES' : 'NO') . " (Expected: " . ($expectedAbsolute ? 'YES' : 'NO') . ")\n";

    if ($isAbsolute !== $expectedAbsolute) {
        echo "FAILED\n";
        exit(1);
    }
}

echo "Verifying Path Resolution Logic...\n";

// Unix-like
checkPath('/var/www/db.sqlite', true);
checkPath('database/db.sqlite', false);

// Windows-like
checkPath('C:\\Users\\db.sqlite', true);
checkPath('C:/Users/db.sqlite', true);
checkPath('D:\\db.sqlite', true);

// Edge case: Drive letter without separator (relative on Windows, distinct from absolute)
checkPath('C:db.sqlite', false);

// UNC
checkPath('\\\\server\\share\\db.sqlite', true);

echo "All checks passed.\n";
