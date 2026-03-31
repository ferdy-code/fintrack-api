<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => config('app.name').' API',
                'version' => '1.0.0',
                'description' => 'FinTrack API for personal finance management',
            ],
            'servers' => [
                [
                    'url' => config('app.url').'/api',
                    'description' => config('app.env') === 'production' ? 'Production' : 'Local',
                ],
            ],
            'paths' => [
                '/v1/auth/register' => [
                    'post' => [
                        'summary' => 'Register a new user',
                        'tags' => ['Authentication'],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'email', 'password', 'password_confirmation'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'maxLength' => 255, 'example' => 'John Doe'],
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                                            'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 8, 'example' => 'password123'],
                                            'password_confirmation' => ['type' => 'string', 'format' => 'password', 'example' => 'password123'],
                                            'default_currency_code' => ['type' => 'string', 'maxLength' => 3, 'example' => 'IDR'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'User registered successfully'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
                '/v1/auth/login' => [
                    'post' => [
                        'summary' => 'Login user',
                        'tags' => ['Authentication'],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['email', 'password'],
                                        'properties' => [
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                                            'password' => ['type' => 'string', 'format' => 'password', 'example' => 'password123'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Login successful'],
                            '422' => ['description' => 'Invalid credentials'],
                        ],
                    ],
                ],
                '/v1/auth/logout' => [
                    'post' => [
                        'summary' => 'Logout user',
                        'tags' => ['Authentication'],
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '200' => ['description' => 'Logged out successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                ],
                '/v1/auth/user' => [
                    'get' => [
                        'summary' => 'Get authenticated user',
                        'tags' => ['Authentication'],
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '200' => ['description' => 'User details'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                    'put' => [
                        'summary' => 'Update user profile',
                        'tags' => ['Authentication'],
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string', 'maxLength' => 255],
                                            'default_currency_code' => ['type' => 'string', 'maxLength' => 3],
                                            'avatar_url' => ['type' => 'string', 'format' => 'uri'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Profile updated successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
                '/v1/auth/password' => [
                    'put' => [
                        'summary' => 'Update user password',
                        'tags' => ['Authentication'],
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['current_password', 'password', 'password_confirmation'],
                                        'properties' => [
                                            'current_password' => ['type' => 'string', 'format' => 'password'],
                                            'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 8],
                                            'password_confirmation' => ['type' => 'string', 'format' => 'password'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Password updated successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'John Doe'],
                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                            'default_currency_code' => ['type' => 'string', 'example' => 'IDR'],
                            'avatar_url' => ['type' => 'string', 'nullable' => true],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'AuthResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'data' => [
                                'type' => 'object',
                                'properties' => [
                                    'user' => ['$ref' => '#/components/schemas/User'],
                                    'token' => ['type' => 'string'],
                                    'token_type' => ['type' => 'string', 'example' => 'Bearer'],
                                ],
                            ],
                        ],
                    ],
                    'ErrorResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                        ],
                    ],
                ],
            ],
        ];

        return response()->json($spec);
    }
}
