<?php

describe('privacy policy page', function () {
    it('is publicly accessible', function () {
        $this->get(route('privacy-policy'))
            ->assertSuccessful();
    });

    it('displays the privacy policy heading', function () {
        $this->get(route('privacy-policy'))
            ->assertSuccessful()
            ->assertSee('Privacy Policy');
    });

    it('contains key privacy sections', function () {
        $this->get(route('privacy-policy'))
            ->assertSuccessful()
            ->assertSee('Information We Collect')
            ->assertSee('How We Use Your Information')
            ->assertSee('Data Storage and Security')
            ->assertSee('Your Rights');
    });

    it('mentions support tickets as the contact method', function () {
        $this->get(route('privacy-policy'))
            ->assertSuccessful()
            ->assertSee('support ticket');
    });
});

describe('terms of service page', function () {
    it('is publicly accessible', function () {
        $this->get(route('terms-of-service'))
            ->assertSuccessful();
    });

    it('displays the terms of service heading', function () {
        $this->get(route('terms-of-service'))
            ->assertSuccessful()
            ->assertSee('Terms of Service');
    });

    it('contains key terms sections', function () {
        $this->get(route('terms-of-service'))
            ->assertSuccessful()
            ->assertSee('Acceptance of Terms')
            ->assertSee('Acceptable Use')
            ->assertSee('Support Tickets')
            ->assertSee('Limitation of Liability');
    });

    it('mentions support tickets as the contact method', function () {
        $this->get(route('terms-of-service'))
            ->assertSuccessful()
            ->assertSee('support ticket');
    });
});
