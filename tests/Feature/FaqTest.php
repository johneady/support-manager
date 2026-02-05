<?php

use App\Models\Faq;
use Livewire\Livewire;

describe('faq page', function () {
    it('is publicly accessible', function () {
        $this->get(route('faq'))
            ->assertSuccessful();
    });

    it('shows published faqs', function () {
        $publishedFaq = Faq::factory()->published()->create(['question' => 'How do I reset my password?']);
        $unpublishedFaq = Faq::factory()->unpublished()->create(['question' => 'Secret FAQ']);

        $this->get(route('faq'))
            ->assertSuccessful()
            ->assertSee('How do I reset my password?')
            ->assertDontSee('Secret FAQ');
    });

    it('displays faqs in sorted order', function () {
        Faq::factory()->published()->create(['question' => 'Question B', 'sort_order' => 2]);
        Faq::factory()->published()->create(['question' => 'Question A', 'sort_order' => 1]);
        Faq::factory()->published()->create(['question' => 'Question C', 'sort_order' => 3]);

        $faqs = Faq::published()->ordered()->get();

        expect($faqs->first()->question)->toBe('Question A')
            ->and($faqs->last()->question)->toBe('Question C');
    });

    it('links to individual faq show pages', function () {
        $faq = Faq::factory()->published()->create(['question' => 'How do I reset my password?']);

        Livewire::test('faq-list')
            ->assertSeeHtml(route('faq.show', $faq));
    });

    it('displays reading time for each faq', function () {
        Faq::factory()->published()->create(['answer' => 'Short answer here.']);

        Livewire::test('faq-list')
            ->assertSee('min read');
    });
});

describe('faq show page', function () {
    it('displays a published faq', function () {
        $faq = Faq::factory()->published()->create([
            'question' => 'How do I reset my password?',
            'answer' => 'Go to **settings** and click *reset*.',
        ]);

        $this->get(route('faq.show', $faq))
            ->assertSuccessful()
            ->assertSee('How do I reset my password?')
            ->assertSee('<strong>settings</strong>', false)
            ->assertSee('<em>reset</em>', false);
    });

    it('returns 404 for unpublished faqs', function () {
        $faq = Faq::factory()->unpublished()->create();

        $this->get(route('faq.show', $faq))
            ->assertNotFound();
    });

    it('returns 404 for non-existent slugs', function () {
        $this->get('/faq/non-existent-slug')
            ->assertNotFound();
    });

    it('displays reading time', function () {
        $faq = Faq::factory()->published()->create(['answer' => 'A short answer.']);

        $this->get(route('faq.show', $faq))
            ->assertSuccessful()
            ->assertSee('min read');
    });

    it('has a back link to the faq index', function () {
        $faq = Faq::factory()->published()->create();

        $this->get(route('faq.show', $faq))
            ->assertSuccessful()
            ->assertSee('Back to all FAQs');
    });
});

describe('faq search', function () {
    it('filters faqs by search term', function () {
        Faq::factory()->published()->create(['question' => 'How do I reset my password?']);
        Faq::factory()->published()->create(['question' => 'How do I contact support?']);

        Livewire::test('faq-list')
            ->assertSee('How do I reset my password?')
            ->assertSee('How do I contact support?')
            ->set('search', 'password')
            ->assertSee('How do I reset my password?')
            ->assertDontSee('How do I contact support?');
    });
});

describe('faq model', function () {
    it('has published scope', function () {
        Faq::factory()->published()->create();
        Faq::factory()->unpublished()->create();

        expect(Faq::published()->count())->toBe(1);
    });

    it('has ordered scope', function () {
        Faq::factory()->create(['sort_order' => 3]);
        Faq::factory()->create(['sort_order' => 1]);
        Faq::factory()->create(['sort_order' => 2]);

        $faqs = Faq::ordered()->get();

        expect($faqs->pluck('sort_order')->toArray())->toBe([1, 2, 3]);
    });

    it('casts is_published to boolean', function () {
        $faq = Faq::factory()->published()->create();

        expect($faq->is_published)->toBeBool()
            ->and($faq->is_published)->toBeTrue();
    });

    it('generates a slug automatically from the question', function () {
        $faq = Faq::factory()->create(['question' => 'How do I reset my password?']);

        expect($faq->slug)->toBe('how-do-i-reset-my-password');
    });

    it('generates unique slugs for duplicate questions', function () {
        $faq1 = Faq::factory()->create(['question' => 'Same Question']);
        $faq2 = Faq::factory()->create(['question' => 'Same Question']);

        expect($faq1->slug)->toBe('same-question')
            ->and($faq2->slug)->toBe('same-question-1');
    });

    it('updates slug when question changes', function () {
        $faq = Faq::factory()->create(['question' => 'Original Question']);

        expect($faq->slug)->toBe('original-question');

        $faq->update(['question' => 'Updated Question']);

        expect($faq->fresh()->slug)->toBe('updated-question');
    });

    it('uses slug for route model binding', function () {
        $faq = Faq::factory()->create(['question' => 'Test Question']);

        expect($faq->getRouteKeyName())->toBe('slug');
    });

    it('renders markdown in the answer', function () {
        $faq = Faq::factory()->create(['answer' => '**bold** and *italic*']);

        expect($faq->renderedAnswer())
            ->toContain('<strong>bold</strong>')
            ->toContain('<em>italic</em>');
    });

    it('generates a plain text summary', function () {
        $faq = Faq::factory()->create(['answer' => '**This is bold** and has some content.']);

        $summary = $faq->summary();

        expect($summary)->not->toContain('<strong>')
            ->and($summary)->toContain('This is bold');
    });

    it('returns reading time of at least 1 minute', function () {
        $faq = Faq::factory()->create(['answer' => 'Short.']);

        expect($faq->readingTime())->toBe(1);
    });

    it('calculates reading time based on word count', function () {
        $faq = Faq::factory()->create(['answer' => implode(' ', array_fill(0, 400, 'word'))]);

        expect($faq->readingTime())->toBe(2);
    });
});
