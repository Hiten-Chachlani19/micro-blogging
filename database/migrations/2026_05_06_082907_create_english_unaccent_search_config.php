<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // CREATE INDEX CONCURRENTLY and DDL on text search objects cannot run inside
    // a transaction block on older Postgres versions.
    public $withinTransaction = false;

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // unaccent is a bundled Postgres extension — ships with all standard installs.
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');

        // english_unaccent wraps the standard English configuration and adds an
        // unaccent step before stemming so that "résumé", "resume", and "résume"
        // all resolve to the same lexeme.
        // IF NOT EXISTS for TEXT SEARCH CONFIGURATION requires PostgreSQL 18+.
        // Use a DO block for idempotency on PostgreSQL 17 and earlier.
        DB::statement(<<<'SQL'
            DO $do$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_ts_config WHERE cfgname = 'english_unaccent'
                ) THEN
                    CREATE TEXT SEARCH CONFIGURATION english_unaccent (COPY = english);
                END IF;
            END
            $do$
        SQL);

        // Override every mapping type that benefits from accent stripping.
        // hword, hword_part, word cover the common token classes for Latin text.
        DB::statement(<<<'SQL'
            ALTER TEXT SEARCH CONFIGURATION english_unaccent
                ALTER MAPPING FOR hword, hword_part, word
                WITH unaccent, english_stem
        SQL);

        // Replace the trigger function so both indexed vectors and query tokens
        // use english_unaccent. CREATE OR REPLACE is idempotent — safe to re-run.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION posts_search_vector_update()
            RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                NEW.search_vector :=
                    setweight(to_tsvector('english_unaccent', coalesce(split_part(NEW.body, '.', 1), '')), 'A') ||
                    setweight(to_tsvector('english_unaccent', coalesce(NEW.body, '')), 'B');
                RETURN NEW;
            END;
            $$
        SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Revert trigger function to hardcoded 'english'.
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

        DB::statement('DROP TEXT SEARCH CONFIGURATION IF EXISTS english_unaccent');
    }
};
