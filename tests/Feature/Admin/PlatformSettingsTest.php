<?php

use App\Health\HealthAlertNotifiable;
use App\Mail\TestEmail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Setting::set('health_check_refresh_interval', 5);
    Setting::set('health_check_interval', 5);
    Setting::set('health_alert_email', null);
});

describe('admin.settings route', function () {
    it('redirects guests', function () {
        $this->get(route('admin.settings'))->assertRedirect(route('login'));
    });

    it('forbids non-admin users', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings'))
            ->assertForbidden();
    });

    it('allows admin users', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.settings'))
            ->assertSuccessful();
    });
});

describe('PlatformSettings component', function () {
    it('loads current settings on mount', function () {
        Setting::set('health_check_refresh_interval', 30);
        Setting::set('health_check_interval', 15);
        Setting::set('health_alert_email', 'ops@example.com');

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->assertSet('healthCheckRefreshInterval', 30)
            ->assertSet('healthCheckInterval', 15)
            ->assertSet('healthAlertEmail', 'ops@example.com');
    });

    it('forbids non-admin users from mounting', function () {
        Livewire::actingAs(User::factory()->create())
            ->test('admin.platform-settings')
            ->assertForbidden();
    });

    it('saves valid settings', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('healthCheckRefreshInterval', 15)
            ->set('healthCheckInterval', 30)
            ->set('healthAlertEmail', 'alerts@example.com')
            ->call('save')
            ->assertHasNoErrors();

        expect(Setting::get('health_check_refresh_interval'))->toBe('15')
            ->and(Setting::get('health_check_interval'))->toBe('30')
            ->and(Setting::get('health_alert_email'))->toBe('alerts@example.com');
    });

    it('saves with blank email to disable alerts', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('healthCheckRefreshInterval', 60)
            ->set('healthAlertEmail', '')
            ->call('save')
            ->assertHasNoErrors();

        expect(Setting::get('health_alert_email'))->toBeNull();
    });

    it('rejects an interval not in the allowed list', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('healthCheckRefreshInterval', 7)
            ->call('save')
            ->assertHasErrors(['healthCheckRefreshInterval' => 'in']);
    });

    /**
     * The scheduler frequency is the direct lever on health-history growth,
     * and routes/console.php only maps a fixed set of values to a native
     * frequency. A step that does not divide 60 misfires, so 45 is offered for
     * the page-refresh timer but must not be offered here.
     */
    it('rejects a scheduler interval that does not divide an hour', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('healthCheckInterval', 45)
            ->call('save')
            ->assertHasErrors(['healthCheckInterval' => 'in']);
    });

    /**
     * A new install has no settings row, so this fallback is what every fresh
     * project runs on. Hourly keeps health-history growth to ~120 rows a day.
     */
    it('defaults a fresh install to hourly health checks', function () {
        Setting::query()->where('key', 'health_check_interval')->delete();
        Setting::clearCache();

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->assertSet('healthCheckInterval', 60);
    });

    it('accepts every scheduler interval the console route maps natively', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach ([1, 5, 10, 15, 30, 60] as $interval) {
            Livewire::actingAs($admin)
                ->test('admin.platform-settings')
                ->set('healthCheckInterval', $interval)
                ->call('save')
                ->assertHasNoErrors();

            expect(Setting::get('health_check_interval'))->toBe((string) $interval);
        }
    });

    it('validates health alert email format', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('healthAlertEmail', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['healthAlertEmail' => 'email']);
    });
});

describe('health alert recipient', function () {
    /**
     * spatie/laravel-health resolves config('health.notifications.notifiable')
     * at send time and calls routeNotificationForMail() on it. config/health.php
     * can only supply the ALERTS_TO_ADDRESS env value — a config file cannot
     * query the database, because `config:cache` would freeze whatever it
     * returned at build time — so the admin-managed address is resolved in
     * App\Health\HealthAlertNotifiable instead.
     */
    it('sends alerts to the address configured in the admin UI', function () {
        Setting::set('health_alert_email', 'ops@example.com');

        expect((new HealthAlertNotifiable)->routeNotificationForMail())
            ->toBe('ops@example.com');
    });

    it('falls back to the env address when no row exists', function () {
        config(['health.notifications.mail.to' => 'env@example.com']);

        Setting::query()->where('key', 'health_alert_email')->delete();
        Setting::clearCache();

        expect((new HealthAlertNotifiable)->routeNotificationForMail())
            ->toBe('env@example.com');
    });

    /**
     * Clearing the field is the admin UI's only off switch: config/health.php
     * hardcodes notifications.enabled to true, so a blank field must not fall
     * through to ALERTS_TO_ADDRESS — or, on a deployment that never set it,
     * mail the literal hello@example.com placeholder.
     *
     * An empty route is what suppresses delivery: MailChannel::send() returns
     * early when routeNotificationFor('mail') is falsy.
     */
    it('disables alerts when the admin clears the field', function () {
        config(['health.notifications.mail.to' => 'env@example.com']);

        Setting::set('health_alert_email', null);

        expect((new HealthAlertNotifiable)->routeNotificationForMail())->toBe([]);
    });

    it('disables alerts when the stored address is an empty string', function () {
        Setting::set('health_alert_email', '');

        expect((new HealthAlertNotifiable)->routeNotificationForMail())->toBe([]);
    });

    it('saving an address in the UI changes where alerts are sent', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('healthAlertEmail', 'newops@example.com')
            ->call('save')
            ->assertHasNoErrors();

        expect((new HealthAlertNotifiable)->routeNotificationForMail())
            ->toBe('newops@example.com');
    });

    it('is the notifiable the health package will actually use', function () {
        expect(config('health.notifications.notifiable'))
            ->toBe(HealthAlertNotifiable::class);
    });
});

describe('Setting model', function () {
    it('gets and sets values', function () {
        Setting::set('health_alert_email', 'test@example.com');

        expect(Setting::get('health_alert_email'))->toBe('test@example.com');
    });

    it('returns default for missing key', function () {
        expect(Setting::get('nonexistent_key', 'fallback'))->toBe('fallback');
    });

    it('clears cache on save', function () {
        Setting::allCached(); // warm cache

        Setting::set('health_alert_email', 'new@example.com');

        expect(Setting::get('health_alert_email'))->toBe('new@example.com');
    });
});

describe('email configuration', function () {
    it('exposes the active mailer settings', function () {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => 'mailer@example.com',
            'mail.from.address' => 'no-reply@example.com',
            'mail.from.name' => 'Support',
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $config = Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->instance()
            ->mailConfiguration();

        expect($config['Mailer'])->toBe('smtp')
            ->and($config['Host'])->toBe('smtp.example.com')
            ->and($config['Port'])->toBe('587')
            ->and($config['Username'])->toBe('mailer@example.com')
            ->and($config['From Address'])->toBe('no-reply@example.com')
            ->and($config['From Name'])->toBe('Support');
    });

    /**
     * The section renders in a browser, so the SMTP credential must never be
     * among the values displayed — an admin does not need to read it back to
     * know whether delivery is configured.
     */
    it('never exposes the mail password', function () {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.password' => 'super-secret-password',
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $config = Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->instance()
            ->mailConfiguration();

        expect($config)->not->toHaveKey('Password')
            ->and($config)->not->toContain('super-secret-password');
    });

    it('renders the email configuration section', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->assertSee('Email Configuration')
            ->assertSee('Send Test Email');
    });

    it('does not leak the mail password into the rendered page', function () {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.password' => 'super-secret-password',
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->assertDontSee('super-secret-password');
    });
});

describe('send test email', function () {
    it('opens the modal prefilled with the health alert address', function () {
        Setting::set('health_alert_email', 'ops@example.com');

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->call('openTestEmailModal')
            ->assertSet('showTestEmailModal', true)
            ->assertSet('testEmailAddress', 'ops@example.com');
    });

    /**
     * With no alert address configured there is still a sensible recipient:
     * the admin who is looking at the page.
     */
    it('falls back to the acting admin address when no alert email is set', function () {
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'admin@example.com']);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->call('openTestEmailModal')
            ->assertSet('testEmailAddress', 'admin@example.com');
    });

    it('sends a test email to the chosen address', function () {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->call('openTestEmailModal')
            ->set('testEmailAddress', 'someone@example.com')
            ->call('sendTestEmail')
            ->assertHasNoErrors()
            ->assertSet('showTestEmailModal', false);

        Mail::assertSent(TestEmail::class, function (TestEmail $mail) {
            return $mail->hasTo('someone@example.com');
        });
    });

    it('sends to an address other than the configured alert email', function () {
        Mail::fake();

        Setting::set('health_alert_email', 'ops@example.com');

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->call('openTestEmailModal')
            ->set('testEmailAddress', 'elsewhere@example.com')
            ->call('sendTestEmail')
            ->assertHasNoErrors();

        Mail::assertSent(TestEmail::class, fn (TestEmail $mail) => $mail->hasTo('elsewhere@example.com'));
        Mail::assertNotSent(TestEmail::class, fn (TestEmail $mail) => $mail->hasTo('ops@example.com'));
    });

    it('requires a recipient address', function () {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('testEmailAddress', '')
            ->call('sendTestEmail')
            ->assertHasErrors(['testEmailAddress' => 'required']);

        Mail::assertNothingSent();
    });

    it('rejects a malformed recipient address', function () {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('testEmailAddress', 'not-an-email')
            ->call('sendTestEmail')
            ->assertHasErrors(['testEmailAddress' => 'email']);

        Mail::assertNothingSent();
    });

    /**
     * Sending is what the button exists to verify, so a validation failure here
     * must not close the modal — the admin needs to see the error and correct
     * the address in place.
     */
    it('keeps the modal open when validation fails', function () {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->call('openTestEmailModal')
            ->set('testEmailAddress', 'not-an-email')
            ->call('sendTestEmail')
            ->assertSet('showTestEmailModal', true);
    });

    /**
     * The mailable is deliberately not ShouldQueue: a queued send would report
     * success on a deployment whose worker is stopped, hiding the very fault
     * the test email is meant to detect.
     */
    it('sends synchronously rather than queueing', function () {
        Mail::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('testEmailAddress', 'someone@example.com')
            ->call('sendTestEmail');

        Mail::assertSent(TestEmail::class);
        Mail::assertNotQueued(TestEmail::class);

        expect(new TestEmail('Admin'))
            ->not->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
    });

    /**
     * A misconfigured mailer throws at send time. That is the common case this
     * feature exists to surface, so the failure must be reported to the admin
     * rather than escalating into an error page.
     */
    it('reports a transport failure instead of throwing', function () {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('Connection refused'));

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->call('openTestEmailModal')
            ->set('testEmailAddress', 'someone@example.com')
            ->call('sendTestEmail')
            ->assertHasErrors('testEmailAddress')
            ->assertSet('showTestEmailModal', true);
    });

    /**
     * mount() already aborts for a non-admin, so a non-admin never reaches a
     * mounted component to call the action on. The send action re-checks
     * anyway, but the reachable guarantee to assert is the mount abort.
     */
    it('forbids non-admin users from reaching the component', function () {
        Mail::fake();

        Livewire::actingAs(User::factory()->create())
            ->test('admin.platform-settings')
            ->assertForbidden();

        Mail::assertNothingSent();
    });

    /**
     * Regression: save() must not validate the test-email field. That field is
     * `required`, and it is empty whenever the admin has not opened the modal,
     * so a blanket $this->validate() blocked saving unrelated settings.
     */
    it('does not block saving settings while the test recipient is empty', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->assertSet('testEmailAddress', '')
            ->set('healthCheckInterval', 30)
            ->call('save')
            ->assertHasNoErrors();

        expect(Setting::get('health_check_interval'))->toBe('30');
    });

    it('renders the test email markdown view', function () {
        $rendered = (new TestEmail('Ada Lovelace'))->render();

        expect($rendered)->toContain('Ada Lovelace');
    });
});
