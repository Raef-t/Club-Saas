<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(title: "Clubs SaaS API", version: "1.0.0", description: "API documentation for Clubs SaaS")]
#[OA\Server(url: "http://localhost/Clubs/public/api", description: "Local Server")]
#[OA\SecurityScheme(securityScheme: "bearerAuth", type: "http", scheme: "bearer", bearerFormat: "JWT")]

#[OA\Schema(
    schema: "ApiResponse",
    properties: [
        new OA\Property(property: "status", type: "string", example: "success"),
        new OA\Property(property: "message", type: 'string', example: "Operation successful"),
        new OA\Property(property: "data", type: "object", nullable: true)
    ]
)]

#[OA\Schema(
    schema: "ApiErrorResponse",
    properties: [
        new OA\Property(property: "status", type: "string", example: "error"),
        new OA\Property(property: "message", type: "string", example: "Something went wrong"),
        new OA\Property(property: "errors", type: "object", nullable: true)
    ]
)]
class SwaggerConfig {}
