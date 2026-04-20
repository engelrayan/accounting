<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature   = 'admin:create';
    protected $description = 'إنشاء حساب مدير جديد للنظام';

    public function handle(): int
    {
        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('     إنشاء حساب مدير — سندباد محاسب');
        $this->info('═══════════════════════════════════════');
        $this->info('');

        // ── Name ──────────────────────────────────────────────────────────────
        $name = $this->ask('الاسم الكامل للمدير');
        if (blank($name)) {
            $this->error('الاسم مطلوب.');
            return self::FAILURE;
        }

        // ── Email ─────────────────────────────────────────────────────────────
        $email = $this->ask('البريد الإلكتروني (للدخول)');
        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
        if ($validator->fails()) {
            $this->error('البريد الإلكتروني غير صالح.');
            return self::FAILURE;
        }
        if (DB::table('users')->where('email', $email)->exists()) {
            $this->error("البريد [{$email}] مستخدم بالفعل.");
            return self::FAILURE;
        }

        // ── Password ──────────────────────────────────────────────────────────
        $password = $this->secret('كلمة المرور (8 أحرف على الأقل)');
        if (strlen($password) < 8) {
            $this->error('كلمة المرور قصيرة — يجب أن تكون 8 أحرف على الأقل.');
            return self::FAILURE;
        }
        $confirm = $this->secret('تأكيد كلمة المرور');
        if ($password !== $confirm) {
            $this->error('كلمتا المرور غير متطابقتين.');
            return self::FAILURE;
        }

        // ── Role ──────────────────────────────────────────────────────────────
        $role = $this->choice('الصلاحية', ['admin', 'accountant', 'viewer'], 0);

        // ── Confirm ───────────────────────────────────────────────────────────
        $this->info('');
        $this->table(
            ['الحقل', 'القيمة'],
            [
                ['الاسم',      $name],
                ['البريد',     $email],
                ['الصلاحية',   $role],
                ['company_id', 1],
            ]
        );

        if (! $this->confirm('هل تريد إنشاء هذا الحساب؟', true)) {
            $this->warn('تم الإلغاء.');
            return self::SUCCESS;
        }

        // ── Insert ────────────────────────────────────────────────────────────
        $id = DB::table('users')->insertGetId([
            'company_id'        => 1,
            'name'              => $name,
            'email'             => $email,
            'email_verified_at' => now(),
            'password'          => Hash::make($password),
            'role'              => $role,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->info('');
        $this->info("✅  تم إنشاء الحساب بنجاح! (ID: {$id})");
        $this->info("    البريد  : {$email}");
        $this->info("    الصلاحية: {$role}");
        $this->info('');

        return self::SUCCESS;
    }
}
