<?php

use Illuminate\Support\Facades\Route;
use Natnaeln4d\MailCustomizer\Http\Controllers\MailTemplateController;

// Prefix and middleware are applied by MailCustomizerServiceProvider (see config/mail-customizer.php).

Route::get('/builder/elements', [MailTemplateController::class, 'getBuilderElements']);

// Stateless preview / test send for templates that have not been saved yet.
Route::post('/templates/preview', [MailTemplateController::class, 'previewDraft']);
Route::post('/templates/send-test', [MailTemplateController::class, 'sendTestDraft']);

Route::post('/templates/{template}/preview', [MailTemplateController::class, 'preview']);
Route::post('/templates/{template}/send-test', [MailTemplateController::class, 'sendTestEmail']);
Route::post('/templates/{template}/send-custom', [MailTemplateController::class, 'sendCustomEmail']);

Route::apiResource('templates', MailTemplateController::class);

Route::post('/upload-image', [MailTemplateController::class, 'uploadImage']);
Route::delete('/delete-image', [MailTemplateController::class, 'deleteImage']);
