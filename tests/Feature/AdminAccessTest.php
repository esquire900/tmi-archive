<?php

namespace Tests\Feature;

use App\Models\Talk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_user_can_reach_dashboard_and_resources(): void
    {
        $admin = User::factory()->admin()->create();
        $talk = Talk::factory()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/talks')->assertOk();
        $this->actingAs($admin)->get('/admin/playlists')->assertOk();
        $this->actingAs($admin)->get("/admin/talks/{$talk->id}/edit")->assertOk();
    }

    public function test_non_admin_cannot_access_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
