<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(title: "Clubs API", version: "1.0.0", description: "API documentation for Clubs")]
#[OA\Server(url: "http://31.70.108.63/api", description: "New Production API Server")]
#[OA\Server(url: "http://issgroup-001-site1.anytempurl.com/api", description: "Legacy Staging Server")]
#[OA\Server(url: "http://127.0.0.1:8000/api", description: "Local Development Server")]
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
