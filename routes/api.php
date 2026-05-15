<?php

use App\Http\Controllers\Slack\EventController;
use App\Http\Controllers\Slack\InteractionController;
use App\Http\Controllers\Slack\SlashCommandController;
use Illuminate\Support\Facades\Route;

Route::name('slack.')->prefix('slack')
    ->middleware(['slack.verify'])
    ->group(function () {
        Route::any('test', function () {
            return response()->json(['ok' => true]);
        })->name('test');

        Route::any('slash/foo', [SlashCommandController::class, 'foo'])->name('slash.foo');

        Route::post('interaction', InteractionController::class)->name('interaction');

        Route::post('event', EventController::class)->name('event');
    });
