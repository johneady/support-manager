<?php

use App\Models\Setting;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|integer|in:1,5,10,15,30,45,60')]
    public int $healthCheckRefreshInterval = 5;

    #[Validate('nullable|email|max:255')]
    public string $healthAlertEmail = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->healthCheckRefreshInterval = (int) Setting::get('health_check_refresh_interval', 5);
        $this->healthAlertEmail = Setting::get('health_alert_email', '') ?? '';
    }

    public function save(): void
    {
        $this->validate();

        Setting::set('health_check_refresh_interval', $this->healthCheckRefreshInterval);
        Setting::set('health_alert_email', $this->healthAlertEmail ?: null);

        session()->flash('success', 'Platform settings saved successfully.');
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
</div>
