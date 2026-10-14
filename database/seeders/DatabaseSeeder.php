<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $username = mb_strtolower(trim((string) env('ADMIN_USERNAME'))); $password = (string) env('ADMIN_PASSWORD');
        if ($username === '' || strlen($password) < 12) throw new RuntimeException('Set ADMIN_USERNAME and an ADMIN_PASSWORD of at least 12 characters.');
        if (! DB::table('library.user')->where('role', 'admin')->exists()) {
            DB::table('library.user')->insert(['id'=>(string)Str::uuid(),'username'=>$username,'password_hash'=>Hash::make($password),'role'=>'admin']);
        }
    }
}
