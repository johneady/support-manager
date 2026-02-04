<?php

use App\Models\Faq;

test('support page loads successfully', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('How can we help you?');
    $response->assertSee('Frequently Asked Questions');
});

test('support page displays published faqs', function () {
    $publishedFaq = Faq::factory()->create([
        'question' => 'Test Published Question',
        'answer' => 'Test Published Answer',
        'is_published' => true,
        'sort_order' => 1,
    ]);

    $unpublishedFaq = Faq::factory()->create([
        'question' => 'Unpublished Question',
        'answer' => 'Unpublished Answer',
        'is_published' => false,
        'sort_order' => 2,
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Test Published Question');
    $response->assertDontSee('Unpublished Question');
});

test('support page shows empty state when no faqs exist', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('No FAQs yet');
});

test('support page displays faqs in correct order', function () {
    Faq::factory()->create([
        'question' => 'Second Question',
        'is_published' => true,
        'sort_order' => 2,
    ]);

    Faq::factory()->create([
        'question' => 'First Question',
        'is_published' => true,
        'sort_order' => 1,
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSeeInOrder(['First Question', 'Second Question']);
});

test('support page shows login and register links for guests', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Log in');
    $response->assertSee('Register');
});

test('support page shows dashboard link for authenticated users', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});
