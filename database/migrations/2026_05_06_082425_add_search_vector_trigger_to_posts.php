<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION posts_search_vector_update()
            RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                NEW.search_vector :=
                    setweight(to_tsvector('english', coalesce(split_part(NEW.body, '.', 1), '')), 'A') ||
                    setweight(to_tsvector('english', coalesce(NEW.body, '')), 'B');
                RETURN NEW;
            END;
            $$
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER posts_search_vector_trigger
            BEFORE INSERT OR UPDATE OF body ON posts
            FOR EACH ROW EXECUTE FUNCTION posts_search_vector_update()
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS posts_search_vector_trigger ON posts');
        DB::statement('DROP FUNCTION IF EXISTS posts_search_vector_update');
    }
};
