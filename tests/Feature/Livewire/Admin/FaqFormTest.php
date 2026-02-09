<?php

use App\Models\Faq;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

describe('faq form access', function () {
    it('requires authentication for create page', function () {
        $this->get(route('admin.faqs.create'))
            ->assertRedirect(route('login'));
    });

    it('requires authentication for edit page', function () {
        $faq = Faq::factory()->create();

        $this->get(route('admin.faqs.edit', $faq->id))
            ->assertRedirect(route('login'));
    });

    it('denies access to non-admin users on create page', function () {
        $this->actingAs($this->user)
            ->get(route('admin.faqs.create'))
            ->assertForbidden();
    });

    it('denies access to non-admin users on edit page', function () {
        $faq = Faq::factory()->create();

        $this->actingAs($this->user)
            ->get(route('admin.faqs.edit', $faq->id))
            ->assertForbidden();
    });

    it('allows admin access to create page', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.faqs.create'))
            ->assertSuccessful();
    });

    it('allows admin access to edit page', function () {
        $faq = Faq::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.faqs.edit', $faq->id))
            ->assertSuccessful();
    });
});

describe('faq creation', function () {
    it('renders create form with empty fields', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-form')
            ->assertSet('faqId', null)
            ->assertSet('question', '')
            ->assertSet('slug', '')
            ->assertSet('answer', '')
            ->assertSet('isPublished', false);
    });

    it('sets default sort order to next available', function () {
        Faq::factory()->create(['sort_order' => 5]);
        Faq::factory()->create(['sort_order' => 10]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-form')
            ->assertSet('sortOrder', 11);
    });

    it('creates a new faq and redirects to list', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-form')
            ->set('question', 'What is the refund policy?')
            ->set('slug', 'refund-policy')
            ->set('answer', 'Refunds are processed within 30 days.')
            ->set('isPublished', true)
            ->set('sortOrder', 5)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.faqs'));

        $this->assertDatabaseHas('faqs', [
            'question' => 'What is the refund policy?',
            'slug' => 'refund-policy',
            'answer' => 'Refunds are processed within 30 days.',
            'is_published' => true,
            'sort_order' => 5,
        ]);
    });

    it('creates unpublished faq by default', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-form')
            ->set('question', 'Draft question')
            ->set('slug', 'draft-question')
            ->set('answer', 'Draft answer')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('faqs', [
            'question' => 'Draft question',
            'slug' => 'draft-question',
            'is_published' => false,
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-form')
            ->set('question', '')
            ->set('answer', '')
            ->call('save')
            ->assertHasErrors(['question', 'answer']);
    });

    it('validates question max length', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-form')
            ->set('question', str_repeat('a', 501))
            ->set('slug', 'valid-slug')
            ->set('answer', 'Valid answer')
            ->call('save')
            ->assertHasErrors(['question']);
    });

    it('validates slug is required', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-form')
            ->set('question', 'Valid question')
            ->set('slug', '')
            ->set('answer', 'Valid answer')
            ->call('save')
            ->assertHasErrors(['slug']);
    });

    it('validates slug is unique', function () {
        Faq::factory()->create(['slug' => 'existing-slug']);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-form')
            ->set('question', 'New question')
            ->set('slug', 'existing-slug')
            ->set('answer', 'Valid answer')
            ->call('save')
            ->assertHasErrors(['slug']);
    });

    it('validates slug max length', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-form')
            ->set('question', 'Valid question')
            ->set('slug', str_repeat('a', 256))
            ->set('answer', 'Valid answer')
            ->call('save')
            ->assertHasErrors(['slug']);
    });
});

describe('faq editing', function () {
    it('renders edit form with existing faq data', function () {
        $faq = Faq::factory()->create([
            'question' => 'Edit Me',
            'slug' => 'edit-me',
            'answer' => 'Some answer',
            'is_published' => true,
            'sort_order' => 7,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-form', ['faqId' => $faq->id])
            ->assertSet('faqId', $faq->id)
            ->assertSet('question', 'Edit Me')
            ->assertSet('slug', 'edit-me')
            ->assertSet('answer', 'Some answer')
            ->assertSet('isPublished', true)
            ->assertSet('sortOrder', 7);
    });

    it('updates faq details and redirects to list', function () {
        $faq = Faq::factory()->create([
            'question' => 'Old Question',
            'slug' => 'old-question',
            'answer' => 'Old Answer',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-form', ['faqId' => $faq->id])
            ->set('question', 'New Question')
            ->set('slug', 'new-question')
            ->set('answer', 'New Answer')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.faqs'));

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question' => 'New Question',
            'slug' => 'new-question',
            'answer' => 'New Answer',
        ]);
    });

    it('updates published status', function () {
        $faq = Faq::factory()->unpublished()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.faq-form', ['faqId' => $faq->id])
            ->set('isPublished', true)
            ->call('save')
            ->assertHasNoErrors();

        expect($faq->fresh()->is_published)->toBeTrue();
    });

    it('updates sort order', function () {
        $faq = Faq::factory()->create(['sort_order' => 1]);

        Livewire::actingAs($this->admin)
            ->test('admin.faq-form', ['faqId' => $faq->id])
            ->set('sortOrder', 99)
            ->call('save')
            ->assertHasNoErrors();

        expect($faq->fresh()->sort_order)->toBe(99);
    });

    it('returns 404 for non-existent faq id', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.faq-form', ['faqId' => 99999]);
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
