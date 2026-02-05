<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('health page loads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('health'))
        ->assertStatus(200)
        ->assertSee('System Health')
        ->assertSee('Run Health Check');
});

test('health page triggers check with fresh parameter', function () {
    $user = User::factory()->create();
    Artisan::spy();

    $this->actingAs($user)
        ->get(route('health', ['fresh' => '1']))
        ->assertStatus(200)
        ->assertSee('System Health');

    Artisan::shouldHaveReceived('call')
        ->once()
        ->with(\Spatie\Health\Commands\RunHealthChecksCommand::class);
});
