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
                '/v1/wallets' => [
                    'get' => [
                        'summary' => 'List user wallets',
                        'tags' => ['Wallets'],
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '200' => ['description' => 'List of wallets'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                    'post' => [
                        'summary' => 'Create a wallet',
                        'tags' => ['Wallets'],
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'type', 'currency_code'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'maxLength' => 255, 'example' => 'BCA Savings'],
                                            'type' => ['type' => 'string', 'enum' => ['bank', 'e_wallet', 'cash', 'credit_card'], 'example' => 'bank'],
                                            'currency_code' => ['type' => 'string', 'maxLength' => 3, 'example' => 'IDR'],
                                            'balance' => ['type' => 'number', 'minimum' => 0, 'example' => 1000000],
                                            'icon' => ['type' => 'string', 'maxLength' => 50],
                                            'color' => ['type' => 'string', 'maxLength' => 7, 'example' => '#3B82F6'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Wallet created successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
                '/v1/wallets/{wallet}' => [
                    'get' => [
                        'summary' => 'Get wallet details',
                        'tags' => ['Wallets'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'wallet', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Wallet details'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                        ],
                    ],
                    'put' => [
                        'summary' => 'Update wallet',
                        'tags' => ['Wallets'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'wallet', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string', 'maxLength' => 255],
                                            'icon' => ['type' => 'string', 'maxLength' => 50],
                                            'color' => ['type' => 'string', 'maxLength' => 7],
                                            'balance' => ['type' => 'number', 'minimum' => 0],
                                            'is_active' => ['type' => 'boolean'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Wallet updated successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                        ],
                    ],
                    'delete' => [
                        'summary' => 'Delete wallet',
                        'tags' => ['Wallets'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'wallet', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Wallet deleted successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                            '422' => ['description' => 'Wallet has existing transactions'],
                        ],
                    ],
                ],
                '/v1/categories' => [
                    'get' => [
                        'summary' => 'List categories (system + user custom)',
                        'tags' => ['Categories'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            ['name' => 'type', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['income', 'expense']], 'description' => 'Filter by type'],
                        ],
                        'responses' => [
                            '200' => ['description' => 'List of categories'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                    'post' => [
                        'summary' => 'Create custom category',
                        'tags' => ['Categories'],
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'type'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'maxLength' => 100, 'example' => 'Coffee'],
                                            'type' => ['type' => 'string', 'enum' => ['income', 'expense'], 'example' => 'expense'],
                                            'icon' => ['type' => 'string', 'maxLength' => 50, 'example' => '☕'],
                                            'color' => ['type' => 'string', 'maxLength' => 7, 'example' => '#8B4513'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Category created successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
                '/v1/categories/{category}' => [
                    'put' => [
                        'summary' => 'Update custom category',
                        'tags' => ['Categories'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'category', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string', 'maxLength' => 100],
                                            'icon' => ['type' => 'string', 'maxLength' => 50],
                                            'color' => ['type' => 'string', 'maxLength' => 7],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Category updated successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Cannot modify system categories'],
                        ],
                    ],
                    'delete' => [
                        'summary' => 'Delete custom category',
                        'tags' => ['Categories'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'category', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Category deleted successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Cannot delete system categories'],
                        ],
                    ],
                ],
                '/v1/transactions' => [
                    'get' => [
                        'summary' => 'List transactions (paginated)',
                        'tags' => ['Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            ['name' => 'wallet_id', 'in' => 'query', 'schema' => ['type' => 'integer'], 'description' => 'Filter by wallet'],
                            ['name' => 'category_id', 'in' => 'query', 'schema' => ['type' => 'integer'], 'description' => 'Filter by category'],
                            ['name' => 'type', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['income', 'expense', 'transfer']], 'description' => 'Filter by type'],
                            ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date'], 'description' => 'Start date'],
                            ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date'], 'description' => 'End date'],
                            ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string'], 'description' => 'Search description/merchant'],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Paginated list of transactions'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                    'post' => [
                        'summary' => 'Create transaction',
                        'tags' => ['Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['wallet_id', 'type', 'amount', 'transaction_date'],
                                        'properties' => [
                                            'wallet_id' => ['type' => 'integer', 'example' => 1],
                                            'category_id' => ['type' => 'integer', 'nullable' => true, 'example' => 1],
                                            'type' => ['type' => 'string', 'enum' => ['income', 'expense', 'transfer'], 'example' => 'expense'],
                                            'amount' => ['type' => 'number', 'minimum' => 0.01, 'example' => 50000],
                                            'description' => ['type' => 'string', 'maxLength' => 500, 'example' => 'Lunch at restaurant'],
                                            'merchant_name' => ['type' => 'string', 'maxLength' => 255, 'example' => 'McDonald\'s'],
                                            'transaction_date' => ['type' => 'string', 'format' => 'date-time', 'example' => '2026-03-31T12:00:00Z'],
                                            'notes' => ['type' => 'string', 'maxLength' => 1000],
                                            'destination_wallet_id' => ['type' => 'integer', 'description' => 'Required when type is transfer'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Transaction created successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
                '/v1/transactions/summary' => [
                    'get' => [
                        'summary' => 'Get transaction summary',
                        'tags' => ['Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date'], 'description' => 'Start date (defaults to start of month)'],
                            ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date'], 'description' => 'End date (defaults to end of month)'],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Income/expense totals grouped by category'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                ],
                '/v1/transactions/{transaction}' => [
                    'get' => [
                        'summary' => 'Get transaction details',
                        'tags' => ['Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'transaction', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Transaction details'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                        ],
                    ],
                    'put' => [
                        'summary' => 'Update transaction',
                        'tags' => ['Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'transaction', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'wallet_id' => ['type' => 'integer'],
                                            'category_id' => ['type' => 'integer', 'nullable' => true],
                                            'type' => ['type' => 'string', 'enum' => ['income', 'expense', 'transfer']],
                                            'amount' => ['type' => 'number', 'minimum' => 0.01],
                                            'description' => ['type' => 'string', 'maxLength' => 500],
                                            'merchant_name' => ['type' => 'string', 'maxLength' => 255],
                                            'transaction_date' => ['type' => 'string', 'format' => 'date-time'],
                                            'notes' => ['type' => 'string', 'maxLength' => 1000],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Transaction updated successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                    'delete' => [
                        'summary' => 'Delete transaction',
                        'tags' => ['Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'transaction', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Transaction deleted successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
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
                    'Wallet' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'BCA Savings'],
                            'type' => ['type' => 'string', 'enum' => ['bank', 'e_wallet', 'cash', 'credit_card'], 'example' => 'bank'],
                            'currency' => [
                                'type' => 'object',
                                'properties' => [
                                    'code' => ['type' => 'string', 'example' => 'IDR'],
                                    'symbol' => ['type' => 'string', 'example' => 'Rp'],
                                ],
                            ],
                            'balance' => ['type' => 'number', 'example' => 1000000],
                            'icon' => ['type' => 'string', 'nullable' => true],
                            'color' => ['type' => 'string', 'nullable' => true, 'example' => '#3B82F6'],
                            'is_active' => ['type' => 'boolean', 'example' => true],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'Category' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'Food & Drink'],
                            'type' => ['type' => 'string', 'enum' => ['income', 'expense'], 'example' => 'expense'],
                            'icon' => ['type' => 'string', 'nullable' => true, 'example' => '🍔'],
                            'color' => ['type' => 'string', 'nullable' => true, 'example' => '#EF4444'],
                            'is_system' => ['type' => 'boolean', 'example' => true],
                            'parent_id' => ['type' => 'integer', 'nullable' => true],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'Transaction' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'type' => ['type' => 'string', 'enum' => ['income', 'expense', 'transfer'], 'example' => 'expense'],
                            'amount' => ['type' => 'number', 'example' => 50000],
                            'currency' => [
                                'type' => 'object',
                                'properties' => [
                                    'code' => ['type' => 'string', 'example' => 'IDR'],
                                    'symbol' => ['type' => 'string', 'example' => 'Rp'],
                                ],
                            ],
                            'description' => ['type' => 'string', 'nullable' => true],
                            'merchant_name' => ['type' => 'string', 'nullable' => true],
                            'transaction_date' => ['type' => 'string', 'format' => 'date-time'],
                            'wallet' => ['$ref' => '#/components/schemas/Wallet'],
                            'category' => ['$ref' => '#/components/schemas/Category'],
                            'ai_categorized' => ['type' => 'boolean'],
                            'ai_confidence' => ['type' => 'number', 'nullable' => true],
                            'notes' => ['type' => 'string', 'nullable' => true],
                            'is_recurring' => ['type' => 'boolean'],
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
                    'TransactionSummary' => [
                        'type' => 'object',
                        'properties' => [
                            'total_income' => ['type' => 'number', 'example' => 5000000],
                            'total_expense' => ['type' => 'number', 'example' => 2500000],
                            'by_category' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'category_id' => ['type' => 'integer'],
                                        'type' => ['type' => 'string', 'enum' => ['income', 'expense']],
                                        'total' => ['type' => 'number'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return response()->json($spec);
    }
}
