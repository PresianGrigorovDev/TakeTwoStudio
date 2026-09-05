<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('video_title', 150)->nullable()->after('video_url');
            $table->date('video_uploaded_at')->nullable()->after('video_title');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->foreignId('author_team_member_id')->nullable()->after('category_id')
                ->constrained('team_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_team_member_id');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['video_title', 'video_uploaded_at']);
        });
    }
};
