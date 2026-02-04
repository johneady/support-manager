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
});
