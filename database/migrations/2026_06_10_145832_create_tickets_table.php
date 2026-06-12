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
        Schema::create("tickets", function (Blueprint $table) {
$table->ulid("id")->primary();
$table->foreignUlid("contact_id")->constrained("contacts");
$table->string("subject");
$table->string("priority"); // low | medium | high | urgent
$table->string("status"); // open | pending | resolved | closed
$table->foreignUlid("assignee_id")->nullable()->constrained("users");
$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("tickets");
    }
};
