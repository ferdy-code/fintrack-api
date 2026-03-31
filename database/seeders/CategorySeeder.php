<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $expenseCategories = [
            ['name' => 'Food & Drink', 'icon' => "\u{1F354}", 'color' => '#EF4444'],
            ['name' => 'Transportation', 'icon' => "\u{1F697}", 'color' => '#F97316'],
            ['name' => 'Shopping', 'icon' => "\u{1F6CD}\u{FE0F}", 'color' => '#F59E0B'],
            ['name' => 'Bills & Utilities', 'icon' => "\u{1F4C4}", 'color' => '#3B82F6'],
            ['name' => 'Entertainment', 'icon' => "\u{1F3AC}", 'color' => '#6366F1'],
            ['name' => 'Health', 'icon' => "\u{1F48A}", 'color' => '#22C55E'],
            ['name' => 'Education', 'icon' => "\u{1F4DA}", 'color' => '#8B5CF6'],
            ['name' => 'Groceries', 'icon' => "\u{1F6D2}", 'color' => '#14B8A6'],
            ['name' => 'Subscriptions', 'icon' => "\u{1F4F1}", 'color' => '#EC4899'],
            ['name' => 'Transfer Out', 'icon' => "\u{2197}\u{FE0F}", 'color' => '#78716C'],
            ['name' => 'Other Expense', 'icon' => "\u{1F4E6}", 'color' => '#06B6D4'],
        ];

        $incomeCategories = [
            ['name' => 'Salary', 'icon' => "\u{1F4B0}", 'color' => '#22C55E'],
            ['name' => 'Freelance', 'icon' => "\u{1F4BB}", 'color' => '#3B82F6'],
            ['name' => 'Investment', 'icon' => "\u{1F4C8}", 'color' => '#F59E0B'],
            ['name' => 'Gift', 'icon' => "\u{1F381}", 'color' => '#EC4899'],
            ['name' => 'Transfer In', 'icon' => "\u{2199}\u{FE0F}", 'color' => '#78716C'],
            ['name' => 'Other Income', 'icon' => "\u{1F4B5}", 'color' => '#14B8A6'],
        ];

        $now = now();

        foreach ($expenseCategories as $category) {
            DB::table('categories')->insert([
                'user_id' => null,
                'name' => $category['name'],
                'type' => 'expense',
                'icon' => $category['icon'],
                'color' => $category['color'],
                'is_system' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($incomeCategories as $category) {
            DB::table('categories')->insert([
                'user_id' => null,
                'name' => $category['name'],
                'type' => 'income',
                'icon' => $category['icon'],
                'color' => $category['color'],
                'is_system' => true,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
