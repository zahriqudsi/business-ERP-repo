<?php

namespace Database\Factories\Landlord;

use App\Models\Landlord\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Landlord rows only: the business database itself is made by TenantProvisioner.
 *
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $id = Str::lower((string) Str::ulid());

        return [
            'id' => $id,
            'name' => fake()->company(),
            'slug' => Str::slug(fake()->unique()->company()),
            'database' => config('tenancy.database_prefix').$id,
            'status' => 'active',
            'owner_email' => fake()->safeEmail(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended', 'suspended_at' => now()]);
    }
}
