<?php

declare(strict_types=1);

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

test('admin can view category management page', function () {
    $this->actingAs($this->admin)
        ->get('/admin/categories')
        ->assertSuccessful();
});

test('admin can delete a category without tickets', function () {
    $category = TicketCategory::factory()->create();

    Livewire::actingAs($this->admin)
        ->test('admin.category-management')
        ->call('confirmDelete', $category->id)
        ->call('deleteCategory')
        ->assertHasNoErrors();

    expect(TicketCategory::find($category->id))->toBeNull();
});

test('admin cannot delete a category with tickets', function () {
    $category = TicketCategory::factory()->create();
    Ticket::factory()->create(['ticket_category_id' => $category->id]);

    Livewire::actingAs($this->admin)
        ->test('admin.category-management')
        ->call('confirmDelete', $category->id)
        ->call('deleteCategory')
        ->assertHasNoErrors()
        ->assertSessionMissing('success');

    // Category should still exist
    expect(TicketCategory::find($category->id))->not->toBeNull();

    // Tickets should still have the category
    expect(Ticket::where('ticket_category_id', $category->id)->count())->toBe(1);
});

test('non-admin cannot access category management', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/categories')
        ->assertForbidden();
});
