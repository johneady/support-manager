<?php

/*
 * Browser coverage for the public pages, which are the densest Flux and
 * Livewire markup an anonymous visitor sees. A feature test proves these
 * routes return 200; only a real browser proves the page still boots --
 * a JavaScript error leaves the marketing page and the FAQ list blank while
 * every server-side assertion stays green.
 */

test('the home page renders without javascript errors', function () {
    visit('/')
        ->assertSee('How can we help you?')
        ->assertNoJavaScriptErrors();
});

test('the faq page renders without javascript errors', function () {
    visit('/faq')
        ->assertSee('Frequently Asked Questions')
        ->assertNoJavaScriptErrors();
});
