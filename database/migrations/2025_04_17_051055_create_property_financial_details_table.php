<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_financial_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            
            // Financial information
            $table->string('FinancialDataSource')->nullable();
            $table->decimal('GrossIncome', 15, 2)->nullable();
            $table->decimal('GrossScheduledIncome', 15, 2)->nullable();
            $table->decimal('NetOperatingIncome', 15, 2)->nullable();
            $table->decimal('TotalActualRent', 15, 2)->nullable();
            
            // Expenses
            $table->decimal('OperatingExpense', 15, 2)->nullable();
            $table->text('OperatingExpenseIncludes')->nullable();
            $table->decimal('InsuranceExpense', 15, 2)->nullable();
            $table->decimal('MaintenanceExpense', 15, 2)->nullable();
            $table->decimal('ManagerExpense', 15, 2)->nullable();
            $table->decimal('NewTaxesExpense', 15, 2)->nullable();
            $table->decimal('OtherExpense', 15, 2)->nullable();
            $table->decimal('SuppliesExpense', 15, 2)->nullable();
            $table->decimal('TrashExpense', 15, 2)->nullable();
            
            // Lease information
            $table->decimal('LeaseAmount', 15, 2)->nullable();
            $table->string('LeaseAmountFrequency')->nullable();
            $table->string('LeaseTerm')->nullable();
            $table->boolean('LeaseRenewalOptionYN')->nullable();
            $table->boolean('LeaseAssignableYN')->nullable();
            $table->string('ExistingLeaseType')->nullable();
            $table->boolean('LeaseConsideredYN')->nullable();
            $table->decimal('LandLeaseAmount', 15, 2)->nullable();
            $table->string('LandLeaseAmountFrequency')->nullable();
            $table->boolean('LandLeaseYN')->nullable();
            $table->text('RentIncludes')->nullable();
            $table->text('TenantPays')->nullable();
            
            // Business information
            $table->string('BusinessName')->nullable();
            $table->string('BusinessType')->nullable();
            $table->integer('NumberOfFullTimeEmployees')->nullable();
            $table->string('CurrentFinancing')->nullable();
            $table->string('SpecialLicenses')->nullable();
            
            // Other financial details
            $table->text('ListingTerms')->nullable();
            $table->text('DocumentsAvailable')->nullable();
            $table->integer('DocumentsCount')->nullable();
            $table->boolean('HomeWarrantyYN')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_financial_details');
    }
};
