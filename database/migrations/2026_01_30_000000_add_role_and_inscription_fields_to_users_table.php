<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Campos auxiliares (si no existen)
            if (!Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }

            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
        });

        // Manejo del ENUM role (MySQL)
        if (Schema::hasColumn('users', 'role')) {
            DB::statement("
                ALTER TABLE users 
                MODIFY role ENUM('admin','client','inscription') 
                NOT NULL 
                DEFAULT 'client'
            ");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'client', 'inscription'])
                    ->default('client')
                    ->after('is_admin');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir ENUM (eliminando inscription)
        if (Schema::hasColumn('users', 'role')) {
            DB::statement("
                ALTER TABLE users 
                MODIFY role ENUM('admin','client') 
                NOT NULL 
                DEFAULT 'client'
            ");
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'company_name')) {
                $table->dropColumn('company_name');
            }

            if (Schema::hasColumn('users', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
