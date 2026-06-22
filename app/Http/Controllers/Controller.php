<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Clubs API Documentation",
 *     version="1.0.0",
 *     description="This is the API documentation for the Clubs system.",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://issgroup-001-site1.anytempurl.com/api",
 *     description="Production API Server"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost/api",
 *     description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Schema(
 *     schema="ApiResponse",
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data", type="object", nullable=true)
 * )
 * 
 * @OA\Schema(
 *     schema="ApiErrorResponse",
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="Something went wrong"),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 */
abstract class Controller
{
    //
}
