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
        Schema::table('bridge_properties', function (Blueprint $table) {
            $table->foreign('list_agent_id')->references('id')->on('bridge_agents')->onDelete('set null');
            $table->foreign('co_list_agent_id')->references('id')->on('bridge_agents')->onDelete('set null');
            $table->foreign('buyer_agent_id')->references('id')->on('bridge_agents')->onDelete('set null');
            $table->foreign('co_buyer_agent_id')->references('id')->on('bridge_agents')->onDelete('set null');
            
            // Change all references from 'offices' to 'bridge_offices'
            $table->foreign('list_office_id')->references('id')->on('bridge_offices')->onDelete('set null');
            $table->foreign('co_list_office_id')->references('id')->on('bridge_offices')->onDelete('set null');
            $table->foreign('buyer_office_id')->references('id')->on('bridge_offices')->onDelete('set null');
            $table->foreign('co_buyer_office_id')->references('id')->on('bridge_offices')->onDelete('set null');
            
            // Change all references from 'schools' to 'bridge_schools'
            $table->foreign('elementary_school_id')->references('id')->on('bridge_schools')->onDelete('set null');
            $table->foreign('middle_school_id')->references('id')->on('bridge_schools')->onDelete('set null');
            $table->foreign('high_school_id')->references('id')->on('bridge_schools')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bridge_properties', function (Blueprint $table) {
            $table->dropForeign(['list_agent_id']);
            $table->dropForeign(['co_list_agent_id']);
            $table->dropForeign(['buyer_agent_id']);
            $table->dropForeign(['co_buyer_agent_id']);
            $table->dropForeign(['list_office_id']);
            $table->dropForeign(['co_list_office_id']);
            $table->dropForeign(['buyer_office_id']);
            $table->dropForeign(['co_buyer_office_id']);
            $table->dropForeign(['elementary_school_id']);
            $table->dropForeign(['middle_school_id']);
            $table->dropForeign(['high_school_id']);
        });
    }
};
