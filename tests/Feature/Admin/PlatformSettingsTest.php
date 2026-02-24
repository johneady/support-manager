<?php

use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Setting::set('health_check_refresh_interval', 5);
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
        Setting::set('health_alert_email', 'ops@example.com');

        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->assertSet('healthCheckRefreshInterval', 30)
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
            ->set('healthAlertEmail', 'alerts@example.com')
            ->call('save')
            ->assertHasNoErrors();

        expect(Setting::get('health_check_refresh_interval'))->toBe('15')
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

    it('validates health alert email format', function () {
        $admin = User::factory()->create(['is_admin' => true]);

        Livewire::actingAs($admin)
            ->test('admin.platform-settings')
            ->set('healthAlertEmail', 'not-an-email')
            ->call('save')
            ->assertHasErrors(['healthAlertEmail' => 'email']);
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
