<?php

use Illuminate\Support\Facades\Route;

// Prefix and middleware are applied by MailCustomizerServiceProvider (config: mail-customizer.ui).

Route::get('/{templateId?}', function ($templateId = null) {
    return view('mail-customizer::builder', [
        'templateId' => $templateId,
        'variables' => config('mail-customizer.default_variables', []),
    ]);
})->whereNumber('templateId')->name('mail-customizer.builder');
