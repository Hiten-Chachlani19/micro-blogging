<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class NplusOneDemoSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory(50)->create();

        Post::factory(2000)
            ->recycle($users)
            ->create();
    }
}