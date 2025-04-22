<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('ListingId')->nullable()->index();
            $table->string('ListingKey')->nullable()->index();
            $table->bigInteger('ListingKeyNumeric')->nullable()->index();
            $table->string('PropertyType')->nullable()->index();
            $table->string('PropertySubType')->nullable()->index();
            $table->string('StandardStatus')->nullable()->index();
            $table->string('MlsStatus')->nullable()->index();
            $table->decimal('ListPrice', 15, 2)->nullable();
            $table->decimal('ClosePrice', 15, 2)->nullable();
            $table->decimal('OriginalListPrice', 15, 2)->nullable();
            $table->decimal('PreviousListPrice', 15, 2)->nullable();
            
            // Location information
            $table->string('StreetNumber')->nullable();
            $table->integer('StreetNumberNumeric')->nullable();
            $table->string('StreetDirPrefix')->nullable();
            $table->string('StreetName')->nullable();
            $table->string('StreetSuffix')->nullable();
            $table->string('StreetDirSuffix')->nullable();
            $table->string('UnitNumber')->nullable();
            $table->string('City')->nullable()->index();
            $table->string('StateOrProvince')->nullable()->index();
            $table->string('PostalCode')->nullable()->index();
            $table->string('PostalCodePlus4')->nullable();
            $table->string('CountyOrParish')->nullable()->index();
            $table->string('SubdivisionName')->nullable()->index();
            $table->string('UnparsedAddress')->nullable();
            $table->string('Country')->nullable();
            $table->decimal('Latitude', 10, 7)->nullable();
            $table->decimal('Longitude', 10, 7)->nullable();

            
            // Basic property details
            $table->integer('BedroomsTotal')->nullable();
            $table->integer('BathroomsFull')->nullable();
            $table->integer('BathroomsHalf')->nullable();
            $table->integer('BathroomsTotalInteger')->nullable();
            $table->integer('RoomsTotal')->nullable();
            $table->decimal('LivingArea', 15, 2)->nullable();
            $table->string('LivingAreaUnits')->nullable();
            $table->integer('LotSizeAcres')->nullable();
            $table->string('LotSizeDimensions')->nullable();
            $table->decimal('LotSizeSquareFeet', 15, 2)->nullable();
            $table->string('LotSizeUnits')->nullable();
            $table->integer('YearBuilt')->nullable()->index();
            $table->string('YearBuiltDetails')->nullable();
            
            // Listing information
            $table->date('ListingContractDate')->nullable();
            $table->date('OnMarketDate')->nullable();
            $table->date('OffMarketDate')->nullable();
            $table->date('CloseDate')->nullable();
            $table->date('ContingentDate')->nullable();
            $table->integer('DaysOnMarket')->nullable();
            $table->string('ListingAgreement')->nullable();
            
            // Agent/Office information
            $table->string('ListAgentFullName')->nullable();
            $table->string('ListAgentKey')->nullable();
            $table->string('ListAgentMlsId')->nullable();
            $table->string('ListAgentEmail')->nullable();
            $table->string('ListAgentDirectPhone')->nullable();
            $table->string('ListOfficeName')->nullable();
            $table->string('ListOfficeKey')->nullable();
            $table->string('ListOfficeMlsId')->nullable();
            $table->string('ListOfficePhone')->nullable();
            
            // Timestamps
            $table->timestamp('OriginalEntryTimestamp')->nullable();
            $table->timestamp('ModificationTimestamp')->nullable();
            $table->timestamp('StatusChangeTimestamp')->nullable();
            $table->timestamp('PriceChangeTimestamp')->nullable();
            $table->timestamp('PhotosChangeTimestamp')->nullable();
            $table->timestamp('PendingTimestamp')->nullable();
            $table->timestamp('MajorChangeTimestamp')->nullable();
            $table->timestamp('OffMarketTimestamp')->nullable();
            
            // Public data
            $table->text('PublicRemarks')->nullable();
            $table->text('SyndicationRemarks')->nullable();
            $table->text('Directions')->nullable();
            $table->text('ShowingInstructions')->nullable();
            $table->text('PrivateRemarks')->nullable();
            
            // Source information
            $table->string('SourceSystemKey')->nullable();
            $table->string('ListingService')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
