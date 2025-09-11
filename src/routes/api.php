<?php

use Illuminate\Support\Facades\Route;
use Natnaeln4d\MailCustomizer\Http\Controllers\MailTemplateController;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/mail-customizer')->group(function () {
    Route::apiResource('templates', MailTemplateController::class);
    Route::post('templates/{id}/preview', [MailTemplateController::class, 'preview']);
    Route::post('templates/{id}/send-test', [MailTemplateController::class, 'sendTestEmail']);
    Route::post('templates/{id}/send-custom', [MailTemplateController::class, 'sendCustomEmail']);

    Route::post('upload-image', [MailTemplateController::class, 'uploadImage']);
    Route::delete('delete-image', [MailTemplateController::class, 'deleteImage']);
});