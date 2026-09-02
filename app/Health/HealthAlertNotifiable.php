<?php

declare(strict_types=1);

namespace App\Health;

use App\Models\Setting;
use Spatie\Health\Notifications\Notifiable;
use Throwable;

/**
 * Routes health-check failure alerts to the address set in the admin UI.
 *
 * config/health.php can only supply the ALERTS_TO_ADDRESS env value, because a
 * config file cannot query the database: `config:cache` would freeze whatever
 * it returned at build time.
 *
 * Resolving the address HERE rather than in a service provider means the
 * settings table is read only when an alert is actually being sent — roughly
 * hourly — instead of on every HTTP request, queue tick and artisan command.
 */
class HealthAlertNotifiable extends Notifiable
{
    /**
     * Three states, in order of precedence:
     *   - a row with an address  → alerts go there
     *   - a row that is blank    → the admin cleared the field: alerts OFF
     *   - no row at all          → fall back to ALERTS_TO_ADDRESS
     *
     * The off state returns an empty array rather than null: MailChannel::send()
     * bails out when the route is falsy, so nothing is delivered, whereas null
     * would be a TypeError against this method's `string|array` return type.
     *
     * Clearing the field is the admin UI's only off switch —
     * config/health.php hardcodes notifications.enabled to true, and a
     * deployment that never set ALERTS_TO_ADDRESS would otherwise mail the
     * literal hello@example.com placeholder.
     */
    public function routeNotificationForMail(): string|array
    {
        $settings = $this->settings();

        if (! array_key_exists('health_alert_email', $settings)) {
            return parent::routeNotificationForMail();
        }

        $recipient = $settings['health_alert_email'];

        return filled($recipient) ? (string) $recipient : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function settings(): array
    {
        try {
            return Setting::allCached();
        } catch (Throwable) {
            return [];
        }
    }
}
