<?php

use Illuminate\Support\Facades\Route;
use Natnaeln4d\MailCustomizer\Http\Controllers\MailTemplateController;

Route::prefix('api/mail-customizer')->group(function () {
    Route::get('/builder/elements', [MailTemplateController::class, 'getBuilderElements']);
    Route::post('/templates/preview', [MailTemplateController::class, 'previewTemplate']);
    Route::post('/templates/send-test', [MailTemplateController::class, 'sendTestEmail']);
    
    Route::apiResource('templates', MailTemplateController::class);
    
    Route::post('/upload-image', [MailTemplateController::class, 'uploadImage']);
    Route::delete('/delete-image', [MailTemplateController::class, 'deleteImage']);
});