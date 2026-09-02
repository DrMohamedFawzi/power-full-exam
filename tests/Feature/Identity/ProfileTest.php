<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_view_their_profile(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_a_user_can_update_their_name_and_email(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->actingAs($user)->put('/profile', [
            'official_name' => 'اسم جديد',
            'email' => 'new-email@example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('اسم جديد', $user->official_name);
        $this->assertSame('new-email@example.com', $user->email);
    }

    public function test_a_user_can_change_their_password(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->put('/profile', [
            'official_name' => $user->official_name,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_a_user_can_replace_their_avatar_and_the_old_file_is_removed(): void
    {
        Storage::fake('public');

        $user = User::factory()->student()->create(['avatar_path' => 'avatars/old.jpg']);
        Storage::disk('public')->put('avatars/old.jpg', 'fake-old-contents');

        $this->actingAs($user)->put('/profile', [
            'official_name' => $user->official_name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('new-avatar.jpg'),
        ]);

        $user->refresh();
        Storage::disk('public')->assertMissing('avatars/old.jpg');
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_a_guest_cannot_reach_the_profile_page(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect(route('login'));
    }
}
