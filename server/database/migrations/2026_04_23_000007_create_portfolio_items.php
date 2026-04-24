<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->string('technologies')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('portfolio_item_proposal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portfolio_item_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['proposal_id', 'portfolio_item_id'], 'pip_unique');
        });

        $now = now();
        DB::table('portfolio_items')->insert([
            [
                'title' => 'PromoSoft',
                'description' => 'Custom software built by industry professionals to organize and automate order fulfillment for apparel decorators.',
                'image' => 'images/thepromosoft.png',
                'url' => 'https://www.thepromosoft.com',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Strokin',
                'description' => 'AI-enabled native application that provides an easy way to score and handicap golf competitions among friends.',
                'image' => 'images/strokin.png',
                'url' => 'https://strokin.app',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Fresh Move Media',
                'description' => 'Custom web application for a digital marketing and video production agency to manage their entire operation in the cloud.',
                'image' => 'images/freshmove.png',
                'url' => 'https://www.freshmovemedia.com',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Secured Link Society',
                'description' => 'Web and native application builds supporting a networking group passing millions in referral business around Richmond, VA.',
                'image' => 'images/sls.png',
                'url' => 'https://www.securedlinksociety.us',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_item_proposal');
        Schema::dropIfExists('portfolio_items');
    }
};
