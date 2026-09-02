<?php

use App\Health\HealthAlertNotifiable;
use App\Models\Setting;
use App\Models\User;
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
