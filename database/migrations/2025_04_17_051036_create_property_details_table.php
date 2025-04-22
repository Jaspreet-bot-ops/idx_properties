<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            
            // Building details
            $table->decimal('BuildingAreaTotal', 15, 2)->nullable();
            $table->string('BuildingAreaSource')->nullable();
            $table->string('StructureType')->nullable();
            $table->string('ArchitecturalStyle')->nullable();
            $table->integer('Stories')->nullable();
            $table->integer('StoriesTotal')->nullable();
            $table->string('Levels')->nullable();
            $table->string('EntryLevel')->nullable();
            $table->string('EntryLocation')->nullable();
            $table->string('CommonWalls')->nullable();
            $table->string('ConstructionMaterials')->nullable();
            $table->string('Roof')->nullable();
            $table->string('PropertyCondition')->nullable();
            
            // Property characteristics
            $table->string('Ownership')->nullable();
            $table->string('OwnershipType')->nullable();
            $table->boolean('NewConstructionYN')->nullable();
            $table->boolean('PropertyAttachedYN')->nullable();
            $table->boolean('HabitableResidenceYN')->nullable();
            $table->string('GreenEnergyEfficient')->nullable();
            $table->string('YearEstablished')->nullable();
            $table->string('DevelopmentStatus')->nullable();
            $table->string('DirectionFaces')->nullable();
            
            // Utilities
            $table->string('Heating')->nullable();
            $table->boolean('HeatingYN')->nullable();
            $table->string('Cooling')->nullable();
            $table->boolean('CoolingYN')->nullable();
            $table->string('Electric')->nullable();
            $table->boolean('ElectricOnPropertyYN')->nullable();
            $table->string('WaterSource')->nullable();
            $table->string('Sewer')->nullable();
            
            // Lot details
            $table->string('LotFeatures')->nullable();
            $table->string('Vegetation')->nullable();
            $table->string('View')->nullable();
            $table->boolean('ViewYN')->nullable();
            $table->string('RoadSurfaceType')->nullable();
            $table->string('RoadFrontageType')->nullable();
            $table->string('RoadResponsibility')->nullable();
            
            // Legal information
            $table->string('ParcelNumber')->nullable();
            $table->string('TaxLot')->nullable();
            $table->string('Zoning')->nullable();
            $table->string('TaxLegalDescription')->nullable();
            $table->decimal('TaxAnnualAmount', 15, 2)->nullable();
            $table->integer('TaxYear')->nullable();
            $table->string('PublicSurveySection')->nullable();
            $table->string('PublicSurveyTownship')->nullable();
            $table->string('PublicSurveyRange')->nullable();
            
            // Possession details
            $table->string('Possession')->nullable();
            $table->string('CurrentUse')->nullable();
            $table->string('PossibleUse')->nullable();
            $table->date('AvailabilityDate')->nullable();
            
            // Waterfront details
            $table->boolean('WaterfrontYN')->nullable();
            $table->string('WaterfrontFeatures')->nullable();
            
            // Other details
            $table->string('Disclosures')->nullable();
            $table->string('SpecialListingConditions')->nullable();
            $table->string('Contingency')->nullable();
            $table->string('MajorChangeType')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_details');
    }
};
