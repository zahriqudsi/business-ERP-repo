<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_change_and_delete_are_logged_with_who_and_what(): void
    {
        $user = $this->signIn();

        $invoice = Invoice::create(['invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-01', 'subtotal' => 100]);
        $invoice->update(['customer_name' => 'Acme Ltd']);
        $invoice->delete();

        $entries = ActivityLog::where('subject_type', 'invoice')->orderBy('id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $entries->pluck('action')->all());
        $this->assertSame($user->id, $entries[0]->user_id);
        $this->assertSame('Invoice #1500 (Acme)', $entries[0]->description);
        $this->assertSame(['customer_name' => ['Acme', 'Acme Ltd']], $entries[1]->changes);
    }

    public function test_noise_and_secrets_are_left_out(): void
    {
        $this->signIn();
        $user = User::create(['username' => 'nimal', 'password' => 'first-password', 'role' => 'staff']);

        $user->update(['password' => 'second-password']);
        $user->forceFill(['last_login_at' => now()])->save();

        $updates = ActivityLog::where('subject_type', 'user')->where('action', 'updated')->get();
        $this->assertCount(1, $updates);
        $this->assertSame(['password' => ['•••', 'changed']], $updates[0]->changes);
    }

    public function test_sign_in_and_stock_adjustments_are_logged(): void
    {
        User::create(['username' => 'boss', 'password' => 'long-enough-1', 'role' => 'owner']);
        $this->post('/login', ['username' => 'boss', 'password' => 'long-enough-1']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'signed_in', 'description' => 'boss signed in']);

        $product = Product::factory()->create(['name' => 'RAM']);
        $this->post("/products/{$product->id}/adjust", ['mode' => 'add', 'quantity' => 4, 'note' => 'Delivery']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'stock_adjusted', 'description' => 'RAM: +4 (Delivery)']);
    }

    public function test_owner_browses_and_filters_the_log(): void
    {
        $this->signIn();
        Invoice::create(['invoice_no' => '1500', 'customer_name' => 'Acme', 'date' => '2026-10-01', 'subtotal' => 100]);
        Product::factory()->create(['name' => 'Charger 65W']);

        $this->get('/activity')->assertOk()->assertSee('Invoice #1500 (Acme)')->assertSee('Product Charger 65W');
        $this->get('/activity?type=product')->assertOk()->assertSee('Charger 65W')->assertDontSee('Invoice #1500');
        $this->get('/activity?search=1500')->assertOk()->assertSee('Invoice #1500')->assertDontSee('Charger 65W');
    }

    public function test_only_owners_see_the_log(): void
    {
        $this->signIn('manager');

        $this->get('/activity')->assertForbidden();
    }
}
