<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        static $seq = 0;
        $seq++;
        return [
            'official_id'  => 'BH' . str_pad((string) (10000 + $seq), 6, '0', STR_PAD_LEFT),
            'username'      => $this->faker->unique()->userName(),
            'email'         => $this->faker->unique()->safeEmail(),
            'password'       => bcrypt('Password123!'),
            'is_active'      => true,
            'level'          => 1,
            'must_change_password' => false,
        ];
    }
}
