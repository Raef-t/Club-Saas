<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(title: "Clubs SaaS API", version: "1.0.0", description: "API documentation for Clubs SaaS")]
#[OA\Server(url: "http://localhost/Clubs/public/api", description: "Local Server")]
#[OA\SecurityScheme(securityScheme: "bearerAuth", type: "http", scheme: "bearer", bearerFormat: "JWT")]
class SwaggerConfig {}
