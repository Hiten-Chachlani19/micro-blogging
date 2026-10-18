<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'body'    => fake()->text(random_int(2000, 2800)),
        ];
    }

    public function reply(Post $parent): static
    {
        return $this->state(['parent_id' => $parent->id]);
    }

    public function repost(Post $original): static
    {
        return $this->state(['repost_of_id' => $original->id]);
    }
}
