<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // For PostgreSQL, we need to alter the enum type
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('user', 'customer', 'driver', 'restaurant', 'admin'))");
        
        // Update default value
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'");
        
        // Optionally migrate existing 'customer' roles to 'user'
        DB::table('users')->where('role', 'customer')->update(['role' => 'user']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('customer', 'driver', 'admin'))");
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'customer'");
        
        // Revert 'user' back to 'customer'
        DB::table('users')->where('role', 'user')->update(['role' => 'customer']);
    }
};
