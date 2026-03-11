<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->index(['is_active', 'parent_id'], 'categories_active_parent_idx');
            $table->index('name', 'categories_name_idx');
            $table->index('name_en', 'categories_name_en_idx');
            $table->index('sort_order', 'categories_sort_order_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'category_id'], 'products_active_category_idx');
            $table->index(['is_active', 'product_type'], 'products_active_type_idx');
            $table->index('name', 'products_name_idx');
            $table->index('name_en', 'products_name_en_idx');
            $table->index('price', 'products_price_idx');
            $table->index('created_at', 'products_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_active_parent_idx');
            $table->dropIndex('categories_name_idx');
            $table->dropIndex('categories_name_en_idx');
            $table->dropIndex('categories_sort_order_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_category_idx');
            $table->dropIndex('products_active_type_idx');
            $table->dropIndex('products_name_idx');
            $table->dropIndex('products_name_en_idx');
            $table->dropIndex('products_price_idx');
            $table->dropIndex('products_created_at_idx');
        });
    }
};
