<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            
            // Interior features
            $table->text('InteriorFeatures')->nullable();
            $table->text('Appliances')->nullable();
            $table->text('Flooring')->nullable();
            $table->text('WindowFeatures')->nullable();
            $table->text('DoorFeatures')->nullable();
            $table->text('LaundryFeatures')->nullable();
            $table->text('AccessibilityFeatures')->nullable();
            $table->boolean('FireplaceYN')->nullable();
            $table->text('FireplaceFeatures')->nullable();
            $table->text('SecurityFeatures')->nullable();
            
            // Exterior features
            $table->text('ExteriorFeatures')->nullable();
            $table->text('PatioAndPorchFeatures')->nullable();
            $table->text('Fencing')->nullable();
            $table->text('OtherStructures')->nullable();
            $table->text('BuildingFeatures')->nullable();
            
            // Parking
            $table->boolean('GarageYN')->nullable();
            $table->boolean('AttachedGarageYN')->nullable();
            $table->integer('GarageSpaces')->nullable();
            $table->integer('CoveredSpaces')->nullable();
            $table->integer('ParkingTotal')->nullable();
            $table->boolean('OpenParkingYN')->nullable();
            $table->text('ParkingFeatures')->nullable();
            
            // Pool/Spa
            $table->boolean('PoolPrivateYN')->nullable();
            $table->text('PoolFeatures')->nullable();
            $table->boolean('SpaYN')->nullable();
            $table->text('SpaFeatures')->nullable();
            
            // Community features
            $table->boolean('AssociationYN')->nullable();
            $table->decimal('AssociationFee', 15, 2)->nullable();
            $table->string('AssociationFeeFrequency')->nullable();
            $table->text('AssociationAmenities')->nullable();
            $table->text('CommunityFeatures')->nullable();
            $table->boolean('SeniorCommunityYN')->nullable();
            $table->integer('NumberOfUnitsInCommunity')->nullable();
            
            // Horse related
            $table->boolean('HorseYN')->nullable();
            $table->text('HorseAmenities')->nullable();
            
            // Other amenities
            $table->text('Utilities')->nullable();
            $table->text('OtherEquipment')->nullable();
            $table->string('Furnished')->nullable();
            $table->text('Inclusions')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_amenities');
    }
};
