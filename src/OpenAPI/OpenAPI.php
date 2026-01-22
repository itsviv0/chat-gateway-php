<?php

/**
 * @OA\OpenApi(
 *     info=@OA\Info(
 *         title="Chat Gateway API",
 *         version="1.0.0",
 *         description="A production-grade chat application backend with JWT authentication and group messaging",
 *         contact=@OA\Contact(
 *             name="API Support",
 *             email="support@chatgateway.local"
 *         ),
 *         license=@OA\License(
 *             name="MIT",
 *             url="https://opensource.org/licenses/MIT"
 *         )
 *     ),
 *     servers={
 *         @OA\Server(
 *             url="http://localhost:8080/api",
 *             description="Local development server",
 *             variables={
 *                 @OA\ServerVariable(
 *                     serverVariable="basePath",
 *                     default="/api"
 *                 )
 *             }
 *         )
 *     }
 * )
 */

/**
 * @OA\SecurityScheme(
 *     type="http",
 *     description="JWT Token Authorization",
 *     name="Authorization",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 */

declare(strict_types=1);

namespace App\OpenAPI;

// This file contains global OpenAPI documentation
