<?php

use App\Mail\TestEmail;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|integer|in:1,5,10,15,30,45,60')]
    public int $healthCheckRefreshInterval = 5;

    /**
     * How often the scheduler RUNS the health checks.
     *
     * Restricted to the values routes/console.php maps to a native Laravel
     * frequency. Anything else falls through to a cron step expression, and a
     * step that does not divide 60 (45, for one) misfires: it restarts at the
     * top of each hour, so the gap between the last run and the next is
     * shorter than the interval asked for. 45 is offered for the page-refresh
     * setting above, which is a browser timer, but deliberately not here.
     *
     * This is the direct lever on health-history growth: one row per check per
     * run, five checks. Hourly is the default because it writes 120 rows a day
     * where every-five-minutes writes 1,440 and every-minute writes 7,200 —
     * an unpruned table had reached 292,820 rows before this was addressed.
     */
    #[Validate('required|integer|in:1,5,10,15,30,60')]
    public int $healthCheckInterval = 60;

    #[Validate('nullable|email|max:255')]
    public string $healthAlertEmail = '';

    public bool $showTestEmailModal = false;

    #[Validate('required|email|max:255')]
    public string $testEmailAddress = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->healthCheckRefreshInterval = (int) Setting::get('health_check_refresh_interval', 5);
        $this->healthCheckInterval = (int) Setting::get('health_check_interval', 60);
        $this->healthAlertEmail = Setting::get('health_alert_email', '') ?? '';
    }

    /**
     * Validates only the settings fields.
     *
     * $this->validate() would also run the test-email rules, and that field is
     * `required` — an empty test recipient (the normal state of the page) would
     * otherwise block saving unrelated settings.
     */
    public function save(): void
    {
        $this->validate([
            'healthCheckRefreshInterval' => 'required|integer|in:1,5,10,15,30,45,60',
            'healthCheckInterval' => 'required|integer|in:1,5,10,15,30,60',
            'healthAlertEmail' => 'nullable|email|max:255',
        ]);

        Setting::set('health_check_refresh_interval', $this->healthCheckRefreshInterval);
        Setting::set('health_check_interval', $this->healthCheckInterval);
        Setting::set('health_alert_email', $this->healthAlertEmail ?: null);

        session()->flash('success', 'Platform settings saved successfully.');
    }

    /**
     * The active outgoing mail settings, for display only.
     *
     * MAIL_PASSWORD is deliberately never included: this renders in a browser
     * and the credential is not something an admin needs to read back to know
     * whether delivery is configured.
     *
     * @return array<string, string|null>
     */
    #[Computed]
    public function mailConfiguration(): array
    {
        $mailer = config('mail.default');
        $transport = config("mail.mailers.{$mailer}.transport", $mailer);

        return [
            'Mailer' => $mailer,
            'Transport' => $transport,
            'Host' => config("mail.mailers.{$mailer}.host"),
            'Port' => ($port = config("mail.mailers.{$mailer}.port")) ? (string) $port : null,
            'Encryption' => config("mail.mailers.{$mailer}.scheme"),
            'Username' => config("mail.mailers.{$mailer}.username"),
            'From Address' => config('mail.from.address'),
            'From Name' => config('mail.from.name'),
        ];
    }

    public function openTestEmailModal(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->resetValidation('testEmailAddress');
        $this->testEmailAddress = $this->healthAlertEmail ?: (auth()->user()?->email ?? '');
        $this->showTestEmailModal = true;
    }

    /**
     * Send the diagnostic email synchronously so a transport failure surfaces
     * here rather than being reported as a success the admin never receives.
     */
    public function sendTestEmail(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->validateOnly('testEmailAddress');

        try {
            Mail::to($this->testEmailAddress)->send(
                new TestEmail(auth()->user()->name)
            );
        } catch (\Throwable $e) {
            report($e);

            $this->addError('testEmailAddress', 'Could not send the test email: '.$e->getMessage());

            return;
        }

        $this->showTestEmailModal = false;

        session()->flash('success', 'Test email sent to '.$this->testEmailAddress.'.');
    }
};
?>

<div class="space-y-6">
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Header Banner --}}
    <div class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="rounded-full bg-white/20 p-3">
                <flux:icon.adjustments-horizontal class="size-8 text-white" />
            </div>
            <div>
                <flux:heading size="2xl" class="text-white">Platform Settings</flux:heading>
                <flux:text class="text-blue-100">Manage global application configuration</flux:text>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Health Check Settings --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
            <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4 bg-zinc-50 dark:bg-zinc-800">
                <div class="flex items-center gap-3">
                    <flux:icon.shield-check class="size-5 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg">Health Monitoring</flux:heading>
                </div>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Configure health check behaviour and alert notifications</flux:text>
            </div>

            <div class="p-6 space-y-5">
                <flux:field>
                    <flux:label>Health Check Frequency</flux:label>
                    <flux:description>How often the scheduler runs the health checks. Shorter intervals detect problems sooner but write more history — five checks run every minute records 7,200 rows a day.</flux:description>
                    <flux:select wire:model="healthCheckInterval" class="w-48">
                        <flux:select.option value="1">Every minute</flux:select.option>
                        <flux:select.option value="5">Every 5 minutes</flux:select.option>
                        <flux:select.option value="10">Every 10 minutes</flux:select.option>
                        <flux:select.option value="15">Every 15 minutes</flux:select.option>
                        <flux:select.option value="30">Every 30 minutes</flux:select.option>
                        <flux:select.option value="60">Hourly</flux:select.option>
                    </flux:select>
                    <flux:error name="healthCheckInterval" />
                </flux:field>

                <flux:field>
                    <flux:label>Health Check Refresh Interval</flux:label>
                    <flux:description>How often the health status page auto-refreshes.</flux:description>
                    <flux:select wire:model="healthCheckRefreshInterval" class="w-48">
                        <flux:select.option value="1">1 minute</flux:select.option>
                        <flux:select.option value="5">5 minutes</flux:select.option>
                        <flux:select.option value="10">10 minutes</flux:select.option>
                        <flux:select.option value="15">15 minutes</flux:select.option>
                        <flux:select.option value="30">30 minutes</flux:select.option>
                        <flux:select.option value="45">45 minutes</flux:select.option>
                        <flux:select.option value="60">60 minutes</flux:select.option>
                    </flux:select>
                    <flux:error name="healthCheckRefreshInterval" />
                </flux:field>

                <flux:field>
                    <flux:label>Health Alert Email</flux:label>
                    <flux:description>Email address to receive health check failure alerts. Leave blank to disable email alerts.</flux:description>
                    <flux:input
                        type="email"
                        wire:model="healthAlertEmail"
                        placeholder="alerts@example.com"
                        class="w-80"
                    />
                    <flux:error name="healthAlertEmail" />
                </flux:field>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                Save Settings
            </flux:button>
        </div>
    </form>

    {{-- Email Configuration --}}
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="border-b border-zinc-200 dark:border-zinc-700 px-6 py-4 bg-zinc-50 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <flux:icon.envelope class="size-5 text-blue-600 dark:text-blue-400" />
                <flux:heading size="lg">Email Configuration</flux:heading>
            </div>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">The outgoing mail settings this application is running with. These are read from the environment and cannot be edited here.</flux:text>
        </div>

        <div class="p-6 space-y-6">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 sm:grid-cols-2">
                @foreach($this->mailConfiguration as $label => $value)
                    <div class="flex flex-col gap-1">
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                        <dd class="font-mono text-sm text-zinc-900 dark:text-zinc-100 break-all">
                            {{ $value ?? '—' }}
                        </dd>
                    </div>
                @endforeach
            </dl>

            @if($this->mailConfiguration['Mailer'] === 'log')
                <flux:callout variant="warning" icon="exclamation-triangle">
                    The <span class="font-mono">log</span> mailer is active, so mail is written to the application log instead of being delivered. A test email will report success without reaching the inbox.
                </flux:callout>
            @endif

            <div class="flex items-center gap-4 pt-2 border-t border-zinc-200 dark:border-zinc-700">
                <flux:button wire:click="openTestEmailModal" variant="primary" icon="paper-airplane" class="bg-blue-600 hover:bg-blue-700">
                    Send Test Email
                </flux:button>
                <flux:text class="text-zinc-500 dark:text-zinc-400">Send a test message to confirm delivery works.</flux:text>
            </div>
        </div>
    </div>

    {{-- Send Test Email Modal --}}
    <flux:modal wire:model.self="showTestEmailModal" class="w-[30vw]! max-w-[30vw]!">
        <div class="space-y-6">
            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.envelope class="size-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg">Send Test Email</flux:heading>
                </div>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">The message is sent immediately using the mailer shown above.</flux:text>
            </div>

            <form wire:submit="sendTestEmail" class="space-y-6">
                <flux:field>
                    <flux:label>Recipient</flux:label>
                    <flux:description>Where the test message should be delivered.</flux:description>
                    <flux:input
                        type="email"
                        wire:model="testEmailAddress"
                        placeholder="you@example.com"
                    />
                    <flux:error name="testEmailAddress" />
                </flux:field>

                <div class="flex items-center gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                        <span wire:loading.remove wire:target="sendTestEmail">Send Test Email</span>
                        <span wire:loading wire:target="sendTestEmail">Sending...</span>
                    </flux:button>
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
