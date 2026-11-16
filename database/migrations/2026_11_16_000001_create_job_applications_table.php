<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Applications sent from the /careers page. The CV itself is a private file under storage/app/careers.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_applications')) {
            return;
        }
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 150);
            $table->string('phone', 40)->nullable();
            $table->string('role', 80);          // slug from config careers.roles, or "general"
            $table->string('role_title', 150);   // the title as shown when they applied
            $table->string('link', 255)->nullable();
            $table->text('message');
            $table->string('cv_path', 255)->nullable();
            $table->string('cv_name', 255)->nullable();
            $table->string('ip', 64)->nullable();
            $table->boolean('spam')->default(false);
            $table->string('spam_reason', 255)->nullable();
            $table->timestamps();
            $table->index(['spam', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
