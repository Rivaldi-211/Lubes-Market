<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_tables_are_created(): void
    {
        foreach (['users', 'kategori', 'umkm', 'produk', 'pesanan', 'ulasan', 'log_aktivitas'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table {$table}");
        }
    }

    public function test_reviews_are_unique_per_order(): void
    {
        $indexes = Schema::getIndexes('ulasan');
        $this->assertTrue(collect($indexes)->contains(fn ($index) => ($index['unique'] ?? false) && in_array('pesanan_id', $index['columns'] ?? [], true)));
    }
}
