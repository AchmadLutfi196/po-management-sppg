<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat kolom `number` pada tabel `purchase_orders` menjadi nullable.
     * Nomor PO hanya diterbitkan setelah admin menentukan supplier untuk semua item.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('number')->nullable(false)->change();
        });
    }
};
