<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:make-admin {email}', function (): int {
    $user = User::where('email', $this->argument('email'))->first();

    if (! $user) {
        $this->error('Không tìm thấy người dùng.');

        return 1;
    }

    $user->is_admin = true;
    $user->save();
    $this->info('Đã cấp quyền quản trị.');

    return 0;
})->purpose('Cấp quyền quản trị hệ thống cho một tài khoản đã có');
