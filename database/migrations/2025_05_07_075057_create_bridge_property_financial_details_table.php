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
        Schema::create('bridge_property_financial_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->foreign('property_id')->references('id')->on('bridge_properties')->onDelete('cascade');
            
            $table->decimal('gross_income', 12, 2)->nullable();
            $table->decimal('gross_scheduled_income', 12, 2)->nullable();
            $table->decimal('net_operating_income', 12, 2)->nullable();
            $table->decimal('total_actual_rent', 12, 2)->nullable();
            $table->decimal('operating_expense', 12, 2)->nullable();
            $table->string('operating_expense_includes')->nullable();
            $table->decimal('insurance_expense', 12, 2)->nullable();
            $table->decimal('maintenance_expense', 12, 2)->nullable();
            $table->decimal('manager_expense', 12, 2)->nullable();
            $table->decimal('new_taxes_expense', 12, 2)->nullable();
            $table->decimal('other_expense', 12, 2)->nullable();
            $table->decimal('supplies_expense', 12, 2)->nullable();
            $table->decimal('trash_expense', 12, 2)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bridge_property_financial_details');
    }
};
