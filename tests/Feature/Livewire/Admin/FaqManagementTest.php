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

describe('faq sort order', function () {
    it('reorders faqs via drag and drop', function () {
        $faqA = Faq::factory()->create(['question' => 'Alpha', 'sort_order' => 0]);
        $faqB = Faq::factory()->create(['question' => 'Bravo', 'sort_order' => 1]);
        $faqC = Faq::factory()->create(['question' => 'Charlie', 'sort_order' => 2]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('reorderFaqs', $faqC->id, 0);

        expect(Faq::ordered()->pluck('question')->toArray())
            ->toBe(['Charlie', 'Alpha', 'Bravo']);
    });

    it('reorders faqs moving item down', function () {
        $faqA = Faq::factory()->create(['question' => 'Alpha', 'sort_order' => 0]);
        $faqB = Faq::factory()->create(['question' => 'Bravo', 'sort_order' => 1]);
        $faqC = Faq::factory()->create(['question' => 'Charlie', 'sort_order' => 2]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('reorderFaqs', $faqA->id, 2);

        expect(Faq::ordered()->pluck('question')->toArray())
            ->toBe(['Bravo', 'Charlie', 'Alpha']);
    });

    it('does not change order when position is the same', function () {
        $faqA = Faq::factory()->create(['question' => 'Alpha', 'sort_order' => 0]);
        $faqB = Faq::factory()->create(['question' => 'Bravo', 'sort_order' => 1]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-management')
            ->call('reorderFaqs', $faqA->id, 0);

        expect(Faq::ordered()->pluck('question')->toArray())
            ->toBe(['Alpha', 'Bravo']);
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
