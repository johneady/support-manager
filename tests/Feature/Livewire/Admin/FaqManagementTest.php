<?php

use App\Models\Faq;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

describe('faq management access', function () {
    it('requires authentication', function () {
        $this->get(route('admin.faqs'))
            ->assertRedirect(route('login'));
    });

    it('denies access to non-admin users', function () {
        $this->actingAs($this->user)
            ->get(route('admin.faqs'))
            ->assertForbidden();
    });

    it('allows access to admin users', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.faqs'))
            ->assertSuccessful();
    });
});

describe('faq listing', function () {
    it('shows all faqs to admin', function () {
        Faq::factory()->create(['question' => 'Test Question One']);
        Faq::factory()->create(['question' => 'Test Question Two']);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->assertSee('Test Question One')
            ->assertSee('Test Question Two');
    });

    it('shows both published and unpublished faqs', function () {
        Faq::factory()->published()->create(['question' => 'Published FAQ']);
        Faq::factory()->unpublished()->create(['question' => 'Draft FAQ']);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->assertSee('Published FAQ')
            ->assertSee('Draft FAQ');
    });

    it('can search faqs by question', function () {
        Faq::factory()->create(['question' => 'How do I reset?']);
        Faq::factory()->create(['question' => 'How do I contact?']);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->set('search', 'reset')
            ->assertSee('How do I reset?')
            ->assertDontSee('How do I contact?');
    });

    it('can search faqs by answer', function () {
        Faq::factory()->create(['question' => 'Question A', 'answer' => 'Click the password link']);
        Faq::factory()->create(['question' => 'Question B', 'answer' => 'Email support team']);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->set('search', 'password')
            ->assertSee('Question A')
            ->assertDontSee('Question B');
    });

    it('displays faqs in sort order', function () {
        Faq::factory()->create(['question' => 'Third', 'sort_order' => 3]);
        Faq::factory()->create(['question' => 'First', 'sort_order' => 1]);
        Faq::factory()->create(['question' => 'Second', 'sort_order' => 2]);

        $this->actingAs($this->admin)
            ->get(route('admin.faqs'))
            ->assertSuccessful()
            ->assertSeeInOrder(['First', 'Second', 'Third']);
    });
});

describe('faq creation', function () {
    it('can open create modal', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true);
    });

    it('creates a new faq', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openCreateModal')
            ->set('question', 'What is the refund policy?')
            ->set('answer', 'Refunds are processed within 30 days.')
            ->set('isPublished', true)
            ->set('sortOrder', 5)
            ->call('createFaq')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('faqs', [
            'question' => 'What is the refund policy?',
            'answer' => 'Refunds are processed within 30 days.',
            'is_published' => true,
            'sort_order' => 5,
        ]);
    });

    it('creates unpublished faq by default', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openCreateModal')
            ->set('question', 'Draft question')
            ->set('answer', 'Draft answer')
            ->call('createFaq')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('faqs', [
            'question' => 'Draft question',
            'is_published' => false,
        ]);
    });

    it('sets default sort order to next available', function () {
        Faq::factory()->create(['sort_order' => 5]);
        Faq::factory()->create(['sort_order' => 10]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openCreateModal')
            ->assertSet('sortOrder', 11);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openCreateModal')
            ->set('question', '')
            ->set('answer', '')
            ->call('createFaq')
            ->assertHasErrors(['question', 'answer']);
    });

    it('validates question max length', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openCreateModal')
            ->set('question', str_repeat('a', 501))
            ->set('answer', 'Valid answer')
            ->call('createFaq')
            ->assertHasErrors(['question']);
    });
});

describe('faq editing', function () {
    it('can open edit modal', function () {
        $faq = Faq::factory()->create(['question' => 'Edit Me']);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openEditModal', $faq->id)
            ->assertSet('showEditModal', true)
            ->assertSet('question', 'Edit Me')
            ->assertSet('editingFaqId', $faq->id);
    });

    it('updates faq details', function () {
        $faq = Faq::factory()->create([
            'question' => 'Old Question',
            'answer' => 'Old Answer',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openEditModal', $faq->id)
            ->set('question', 'New Question')
            ->set('answer', 'New Answer')
            ->call('updateFaq')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'New Question',
            'answer' => 'New Answer',
        ]);
    });

    it('updates published status', function () {
        $faq = Faq::factory()->unpublished()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openEditModal', $faq->id)
            ->set('isPublished', true)
            ->call('updateFaq')
            ->assertHasNoErrors();

        expect($faq->fresh()->is_published)->toBeTrue();
    });

    it('updates sort order', function () {
        $faq = Faq::factory()->create(['sort_order' => 1]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('openEditModal', $faq->id)
            ->set('sortOrder', 99)
            ->call('updateFaq')
            ->assertHasNoErrors();

        expect($faq->fresh()->sort_order)->toBe(99);
    });
});

describe('faq deletion', function () {
    it('can open delete confirmation', function () {
        $faq = Faq::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('confirmDelete', $faq->id)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSet('deletingFaqId', $faq->id);
    });

    it('deletes faq', function () {
        $faq = Faq::factory()->create(['question' => 'Delete Me']);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('confirmDelete', $faq->id)
            ->call('deleteFaq')
            ->assertSet('showDeleteConfirmation', false);

        $this->assertDatabaseMissing('faqs', ['question' => 'Delete Me']);
    });

    it('can cancel delete', function () {
        $faq = Faq::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('confirmDelete', $faq->id)
            ->call('cancelDelete')
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('deletingFaqId', null);

        $this->assertDatabaseHas('faqs', ['id' => $faq->id]);
    });
});

describe('faq toggle published', function () {
    it('can toggle published status from list', function () {
        $faq = Faq::factory()->unpublished()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('togglePublished', $faq->id);

        expect($faq->fresh()->is_published)->toBeTrue();
    });

    it('can unpublish a published faq', function () {
        $faq = Faq::factory()->published()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('togglePublished', $faq->id);

        expect($faq->fresh()->is_published)->toBeFalse();
    });
});
