<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE posts ADD COLUMN search_vector tsvector');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE posts DROP COLUMN search_vector');
    }
};
