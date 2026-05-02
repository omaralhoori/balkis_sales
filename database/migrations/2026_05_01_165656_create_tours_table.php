<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('خاص VIP'); // خاص VIP, مجموعة Group
            $table->text('short_description')->nullable();
            $table->string('external_link')->nullable();
            $table->decimal('default_buying_price', 10, 2)->default(0);
            $table->decimal('default_selling_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
