<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L09 (Mức 2): nền tảng cho trang "Thiết lập tài khoản" chuẩn SaaS.
 *
 *  - users.locale        : ngôn ngữ giao diện (vi | en), mặc định 'vi'.
 *  - users.timezone      : múi giờ hiển thị (vd Asia/Ho_Chi_Minh), null = mặc định app.
 *  - users.hide_online   : ẩn trạng thái online/offline (PresenceService tôn trọng).
 *  - users.invite_policy : ai được mời mình vào nhóm: everyone | classmates | none.
 *  - users.avatar_path   : ảnh đại diện (disk 'public'), null = dùng chữ cái đầu.
 *  - login_histories     : lịch sử đăng nhập (ip + user_agent) để hiện ở tab Bảo mật
 *                          và cảnh báo khi đăng nhập từ IP lạ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->default('vi')->after('role');
            $table->string('timezone', 64)->nullable()->after('locale');
            $table->boolean('hide_online')->default(false)->after('timezone');
            $table->string('invite_policy', 16)->default('everyone')->after('hide_online');
            $table->string('avatar_path')->nullable()->after('invite_policy');
        });

        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'timezone', 'hide_online', 'invite_policy', 'avatar_path']);
        });
    }
};
