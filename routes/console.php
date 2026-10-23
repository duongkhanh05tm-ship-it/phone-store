<?php

use Illuminate\Foundation\Inspiring;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:grant-admin {email}', function (string $email) {
    if (app()->environment('production')) {
        $this->error('Lệnh này chỉ được phép chạy ngoài môi trường production.');
        return self::FAILURE;
    }

    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error('Không tìm thấy tài khoản với email này.');
        return self::FAILURE;
    }

    if ($user->role === 'admin') {
        $this->info('Tài khoản đã có quyền admin.');
        return self::SUCCESS;
    }

    if (! $this->confirm("Cấp quyền admin cho {$user->email}?", false)) {
        $this->info('Đã hủy.');
        return self::SUCCESS;
    }

    $user->forceFill(['role' => 'admin'])->save();
    $this->info('Đã cấp quyền admin thành công.');

    return self::SUCCESS;
})->purpose('Cấp quyền admin cho tài khoản đã tồn tại ngoài production');
