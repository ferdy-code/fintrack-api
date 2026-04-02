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
                '/v1/budgets/overview' => [
                    'get' => [
                        'summary' => 'Get budget overview',
                        'tags' => ['Budgets'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            ['name' => 'period', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['weekly', 'monthly', 'yearly'], 'default' => 'monthly'], 'description' => 'Budget period filter'],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Budget overview with all budgets and their spending stats'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                ],
                '/v1/budgets' => [
                    'get' => [
                        'summary' => 'List budgets',
                        'tags' => ['Budgets'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            ['name' => 'period', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['weekly', 'monthly', 'yearly']], 'description' => 'Filter by period type'],
                            ['name' => 'is_active', 'in' => 'query', 'schema' => ['type' => 'boolean'], 'description' => 'Filter active budgets'],
                        ],
                        'responses' => [
                            '200' => ['description' => 'List of budgets with spending stats'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                    'post' => [
                        'summary' => 'Create a budget',
                        'tags' => ['Budgets'],
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'amount', 'period', 'start_date', 'category_ids'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'maxLength' => 255, 'example' => 'Monthly Food Budget'],
                                            'amount' => ['type' => 'number', 'minimum' => 0.01, 'example' => 2000000],
                                            'period' => ['type' => 'string', 'enum' => ['weekly', 'monthly', 'yearly'], 'example' => 'monthly'],
                                            'start_date' => ['type' => 'string', 'format' => 'date', 'example' => '2026-04-01'],
                                            'end_date' => ['type' => 'string', 'format' => 'date', 'description' => 'Auto-calculated if not provided'],
                                            'category_ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'example' => [1, 2]],
                                            'wallet_id' => ['type' => 'integer', 'description' => 'Optional wallet filter'],
                                            'alert_threshold' => ['type' => 'number', 'minimum' => 0, 'maximum' => 100, 'example' => 80, 'description' => 'Percentage threshold for alerts'],
                                            'description' => ['type' => 'string', 'maxLength' => 500],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Budget created successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '422' => ['description' => 'Validation error or duplicate budget'],
                        ],
                    ],
                ],
                '/v1/budgets/{budget}' => [
                    'get' => [
                        'summary' => 'Get budget details with spending stats',
                        'tags' => ['Budgets'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'budget', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Budget details with spending stats'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                        ],
                    ],
                    'put' => [
                        'summary' => 'Update budget',
                        'tags' => ['Budgets'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'budget', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string', 'maxLength' => 255],
                                            'amount' => ['type' => 'number', 'minimum' => 0.01],
                                            'period' => ['type' => 'string', 'enum' => ['weekly', 'monthly', 'yearly']],
                                            'start_date' => ['type' => 'string', 'format' => 'date'],
                                            'end_date' => ['type' => 'string', 'format' => 'date'],
                                            'category_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                                            'wallet_id' => ['type' => 'integer'],
                                            'alert_threshold' => ['type' => 'number', 'minimum' => 0, 'maximum' => 100],
                                            'is_active' => ['type' => 'boolean'],
                                            'description' => ['type' => 'string', 'maxLength' => 500],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Budget updated successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                    'delete' => [
                        'summary' => 'Delete budget',
                        'tags' => ['Budgets'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'budget', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Budget deleted successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                        ],
                    ],
                ],
                '/v1/recurring-transactions' => [
                    'get' => [
                        'summary' => 'List recurring transactions',
                        'tags' => ['Recurring Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [
                            ['name' => 'is_active', 'in' => 'query', 'schema' => ['type' => 'boolean'], 'description' => 'Filter active recurring transactions'],
                            ['name' => 'type', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['income', 'expense', 'transfer']], 'description' => 'Filter by type'],
                        ],
                        'responses' => [
                            '200' => ['description' => 'List of recurring transactions'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                    'post' => [
                        'summary' => 'Create recurring transaction',
                        'tags' => ['Recurring Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['wallet_id', 'type', 'amount', 'frequency', 'start_date'],
                                        'properties' => [
                                            'wallet_id' => ['type' => 'integer', 'example' => 1],
                                            'category_id' => ['type' => 'integer', 'nullable' => true],
                                            'type' => ['type' => 'string', 'enum' => ['income', 'expense', 'transfer'], 'example' => 'expense'],
                                            'amount' => ['type' => 'number', 'minimum' => 0.01, 'example' => 500000],
                                            'description' => ['type' => 'string', 'maxLength' => 500, 'example' => 'Monthly rent'],
                                            'frequency' => ['type' => 'string', 'enum' => ['daily', 'weekly', 'monthly', 'yearly'], 'example' => 'monthly'],
                                            'start_date' => ['type' => 'string', 'format' => 'date', 'example' => '2026-04-01'],
                                            'end_date' => ['type' => 'string', 'format' => 'date', 'description' => 'Optional end date'],
                                            'destination_wallet_id' => ['type' => 'integer', 'description' => 'Required when type is transfer'],
                                            'auto_create' => ['type' => 'boolean', 'default' => true, 'description' => 'Auto-create transactions'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Recurring transaction created successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
                '/v1/recurring-transactions/{recurringTransaction}' => [
                    'get' => [
                        'summary' => 'Get recurring transaction details',
                        'tags' => ['Recurring Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'recurringTransaction', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Recurring transaction details'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                        ],
                    ],
                    'put' => [
                        'summary' => 'Update recurring transaction',
                        'tags' => ['Recurring Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'recurringTransaction', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'amount' => ['type' => 'number', 'minimum' => 0.01],
                                            'description' => ['type' => 'string', 'maxLength' => 500],
                                            'frequency' => ['type' => 'string', 'enum' => ['daily', 'weekly', 'monthly', 'yearly']],
                                            'end_date' => ['type' => 'string', 'format' => 'date'],
                                            'is_active' => ['type' => 'boolean'],
                                            'auto_create' => ['type' => 'boolean'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Recurring transaction updated successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                    'delete' => [
                        'summary' => 'Delete recurring transaction',
                        'tags' => ['Recurring Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'recurringTransaction', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Recurring transaction deleted successfully'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                        ],
                    ],
                ],
                '/v1/recurring-transactions/{recurringTransaction}/skip' => [
                    'post' => [
                        'summary' => 'Skip next occurrence',
                        'tags' => ['Recurring Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'recurringTransaction', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Next occurrence skipped'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                        ],
                    ],
                ],
                '/v1/recurring-transactions/{recurringTransaction}/process' => [
                    'post' => [
                        'summary' => 'Process recurring transaction now',
                        'tags' => ['Recurring Transactions'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'recurringTransaction', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Recurring transaction processed, transaction created'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Forbidden'],
                            '422' => ['description' => 'Cannot process inactive or expired recurring transaction'],
                        ],
                    ],
                ],
                '/v1/dashboard' => [
                    'get' => [
                        'summary' => 'Get dashboard data',
                        'tags' => ['Dashboard'],
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '200' => ['description' => 'Dashboard with balance, month summary, trends, budget alerts, recent transactions'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                ],
                '/v1/ai/categorize' => [
                    'post' => [
                        'summary' => 'AI transaction categorization',
                        'tags' => ['AI'],
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['description', 'amount', 'type'],
                                        'properties' => [
                                            'description' => ['type' => 'string', 'example' => 'Lunch at McDonalds'],
                                            'merchant_name' => ['type' => 'string', 'nullable' => true, 'example' => 'McDonalds'],
                                            'amount' => ['type' => 'number', 'example' => 50000],
                                            'type' => ['type' => 'string', 'enum' => ['income', 'expense'], 'example' => 'expense'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Category suggestion with confidence score'],
                            '401' => ['description' => 'Unauthenticated'],
                            '429' => ['description' => 'Rate limit exceeded'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
                '/v1/ai/insights' => [
                    'get' => [
                        'summary' => 'Get AI financial insights',
                        'tags' => ['AI'],
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '200' => ['description' => 'Array of financial insights'],
                            '401' => ['description' => 'Unauthenticated'],
                            '429' => ['description' => 'Rate limit exceeded'],
                        ],
                    ],
                ],
                '/v1/ai/chat' => [
                    'post' => [
                        'summary' => 'AI chat (SSE stream)',
                        'tags' => ['AI'],
                        'security' => [['BearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['message'],
                                        'properties' => [
                                            'message' => ['type' => 'string', 'maxLength' => 2000, 'example' => 'How can I reduce my food expenses?'],
                                            'session_id' => ['type' => 'integer', 'nullable' => true, 'description' => 'Existing session ID to continue conversation'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'SSE stream with AI response chunks'],
                            '401' => ['description' => 'Unauthenticated'],
                            '429' => ['description' => 'Rate limit exceeded'],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
                '/v1/ai/chat/sessions' => [
                    'get' => [
                        'summary' => 'List chat sessions',
                        'tags' => ['AI'],
                        'security' => [['BearerAuth' => []]],
                        'responses' => [
                            '200' => ['description' => 'List of chat sessions'],
                            '401' => ['description' => 'Unauthenticated'],
                        ],
                    ],
                ],
                '/v1/ai/chat/sessions/{id}' => [
                    'get' => [
                        'summary' => 'Get chat session messages',
                        'tags' => ['AI'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Chat session with messages'],
                            '401' => ['description' => 'Unauthenticated'],
                            '404' => ['description' => 'Session not found'],
                        ],
                    ],
                    'delete' => [
                        'summary' => 'Delete chat session',
                        'tags' => ['AI'],
                        'security' => [['BearerAuth' => []]],
                        'parameters' => [['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                        'responses' => [
                            '200' => ['description' => 'Chat session deleted'],
                            '401' => ['description' => 'Unauthenticated'],
                            '404' => ['description' => 'Session not found'],
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
                    'Budget' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'Monthly Food Budget'],
                            'amount' => ['type' => 'number', 'example' => 2000000],
                            'spent' => ['type' => 'number', 'example' => 1500000],
                            'remaining' => ['type' => 'number', 'example' => 500000],
                            'percentage_used' => ['type' => 'number', 'example' => 75],
                            'is_over_budget' => ['type' => 'boolean', 'example' => false],
                            'period' => ['type' => 'string', 'enum' => ['weekly', 'monthly', 'yearly'], 'example' => 'monthly'],
                            'start_date' => ['type' => 'string', 'format' => 'date'],
                            'end_date' => ['type' => 'string', 'format' => 'date'],
                            'days_remaining' => ['type' => 'integer', 'example' => 15],
                            'is_active' => ['type' => 'boolean', 'example' => true],
                            'alert_threshold' => ['type' => 'number', 'nullable' => true, 'example' => 80],
                            'categories' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/Category'],
                            ],
                            'wallet' => ['$ref' => '#/components/schemas/Wallet'],
                        ],
                    ],
                    'RecurringTransaction' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'type' => ['type' => 'string', 'enum' => ['income', 'expense', 'transfer'], 'example' => 'expense'],
                            'amount' => ['type' => 'number', 'example' => 500000],
                            'description' => ['type' => 'string', 'example' => 'Monthly rent'],
                            'frequency' => ['type' => 'string', 'enum' => ['daily', 'weekly', 'monthly', 'yearly'], 'example' => 'monthly'],
                            'start_date' => ['type' => 'string', 'format' => 'date'],
                            'end_date' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                            'next_date' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                            'last_processed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                            'is_active' => ['type' => 'boolean', 'example' => true],
                            'auto_create' => ['type' => 'boolean', 'example' => true],
                            'wallet' => ['$ref' => '#/components/schemas/Wallet'],
                            'category' => ['$ref' => '#/components/schemas/Category'],
                            'destination_wallet' => ['$ref' => '#/components/schemas/Wallet'],
                        ],
                    ],
                    'Dashboard' => [
                        'type' => 'object',
                        'properties' => [
                            'total_balance' => ['type' => 'number', 'example' => 10000000],
                            'month_summary' => [
                                'type' => 'object',
                                'properties' => [
                                    'income' => ['type' => 'number'],
                                    'expense' => ['type' => 'number'],
                                    'net' => ['type' => 'number'],
                                    'transaction_count' => ['type' => 'integer'],
                                ],
                            ],
                            'category_breakdown' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'category_id' => ['type' => 'integer'],
                                        'category_name' => ['type' => 'string'],
                                        'total' => ['type' => 'number'],
                                        'percentage' => ['type' => 'number'],
                                    ],
                                ],
                            ],
                            'budget_alerts' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'budget_id' => ['type' => 'integer'],
                                        'name' => ['type' => 'string'],
                                        'percentage_used' => ['type' => 'number'],
                                        'is_over_budget' => ['type' => 'boolean'],
                                    ],
                                ],
                            ],
                            'recent_transactions' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/Transaction'],
                            ],
                            'monthly_trend' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'month' => ['type' => 'string', 'example' => '2026-03'],
                                        'income' => ['type' => 'number'],
                                        'expense' => ['type' => 'number'],
                                        'net' => ['type' => 'number'],
                                    ],
                                ],
                            ],
                            'wallet_balances' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/Wallet'],
                            ],
                        ],
                    ],
                    'AiCategorizeResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'category_id' => ['type' => 'integer', 'nullable' => true],
                            'category_name' => ['type' => 'string', 'nullable' => true],
                            'confidence' => ['type' => 'number', 'example' => 0.95],
                        ],
                    ],
                    'AiInsight' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'example' => 'High food spending detected'],
                            'description' => ['type' => 'string'],
                            'potential_savings' => ['type' => 'number', 'nullable' => true],
                            'priority' => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                            'category' => ['type' => 'string', 'enum' => ['spending', 'saving', 'budget', 'income', 'recurring']],
                        ],
                    ],
                    'AiChatSession' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'title' => ['type' => 'string', 'nullable' => true],
                            'messages_count' => ['type' => 'integer'],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                            'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                ],
            ],
        ];

        return response()->json($spec);
    }
}
