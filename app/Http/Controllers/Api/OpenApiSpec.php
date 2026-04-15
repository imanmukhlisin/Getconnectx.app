<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

/**
 * ConnectX OpenAPI specification entrypoint.
 *
 * This class holds the global @OA\Info, @OA\Server, and security scheme definitions
 * so that l5-swagger can always locate them when scanning the app/ directory.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'ConnectX API',
    description: 'ConnectX — Startup Networking & Matchmaking Platform. All endpoints require a Sanctum Bearer token unless marked public.',
    contact: new OA\Contact(email: 'developer@connectx.app'),
    license: new OA\License(name: 'Apache 2.0', url: 'http://www.apache.org/licenses/LICENSE-2.0.html'),
)]
#[OA\Server(
    url: 'L5_SWAGGER_CONST_HOST',
    description: 'API Server',
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    description: 'Enter your Bearer token: **Bearer {token}**',
    name: 'Authorization',
    in: 'header',
)]
class OpenApiSpec
{
    // This class exists solely to hold global OpenAPI annotations.
    // It has no methods or logic — do not instantiate it.
}
