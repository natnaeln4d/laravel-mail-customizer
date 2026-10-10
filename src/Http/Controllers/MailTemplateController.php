<?php

namespace Natnaeln4d\MailCustomizer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Natnaeln4d\MailCustomizer\Models\MailTemplate;
use Natnaeln4d\MailCustomizer\Services\TemplateCompiler;
use Natnaeln4d\MailCustomizer\Services\DragDropBuilder;
use Natnaeln4d\MailCustomizer\Mail\CustomMailable;
use Natnaeln4d\MailCustomizer\Http\Resources\MailTemplateResource;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class MailTemplateController extends Controller
{
    protected $compiler;
    protected $builder;
    
    public function __construct(TemplateCompiler $compiler, DragDropBuilder $builder)
    {
        $this->compiler = $compiler;
        $this->builder = $builder;
    }
    
    public function index()
    {
        $templates = MailTemplate::all();
        return MailTemplateResource::collection($templates);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'structure' => 'nullable|array', 
            'styles' => 'nullable|array',
            'images' => 'nullable|array', 
        ]);
        
        $content = $request->content;
        if (!empty($request->structure)) {
            $content = $this->builder->generateHtmlFromStructure($request->structure, []);
        }
        
        $template = MailTemplate::create([
            'name' => $request->name,
            'subject' => $request->subject,
            'content' => $content,
            'structure' => $request->structure, 
            'styles' => $request->styles,
            'images' => $request->images, 
            'layout' => $request->layout ?? 'default',
        ]);
        
        return new MailTemplateResource($template);
    }
    
    public function show($id)
    {
        $template = MailTemplate::findOrFail($id);
        return new MailTemplateResource($template);
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'structure' => 'nullable|array',
            'styles' => 'nullable|array',
            'images' => 'nullable|array', 
        ]);
        
        $content = $request->content;
        if (!empty($request->structure)) {
            $content = $this->builder->generateHtmlFromStructure($request->structure, []);
        }
        
        $template = MailTemplate::findOrFail($id);
        $template->update([
            'name' => $request->name,
            'subject' => $request->subject,
            'content' => $content,
            'structure' => $request->structure, 
            'styles' => $request->styles,
            'images' => $request->images, 
            'layout' => $request->layout ?? 'default',
        ]);
        
        return new MailTemplateResource($template);
    }
    
    public function getBuilderElements()
    {
        return response()->json([
            'elements' => $this->builder->getElements()
        ]);
    }
    
    
    public function destroy($id)
    {
        $template = MailTemplate::findOrFail($id);
        $template->delete();
        
        return response()->json(['message' => 'Template deleted successfully']);
    }
    
    public function preview(Request $request, $id)
    {
        $template = MailTemplate::findOrFail($id);
        $compiled = $this->compiler->compile($template, $this->variablesFromRequest($request));
        
        return response()->json(['preview' => $compiled]);
    }
    
    public function previewDraft(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'structure' => 'nullable|array',
            'styles' => 'nullable|array',
            'images' => 'nullable|array',
            'variables' => 'nullable|array',
        ]);

        $compiled = $this->compiler->compile(
            $this->draftFromRequest($request),
            $this->variablesFromRequest($request)
        );

        return response()->json(['preview' => $compiled]);
    }

    public function sendTestEmail(Request $request, $id)
    {
        $template = MailTemplate::findOrFail($id);

        return $this->deliverTest($request, $template);
    }

    public function sendTestDraft(Request $request)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'structure' => 'nullable|array',
            'styles' => 'nullable|array',
            'images' => 'nullable|array',
            'variables' => 'nullable|array',
        ]);

        return $this->deliverTest($request, $this->draftFromRequest($request));
    }

    protected function deliverTest(Request $request, MailTemplate $template)
    {
        $request->validate(['email' => 'nullable|email']);

        $email = $request->input('email') ?? optional($request->user())->email
            ?? config('mail-customizer.testing.default_test_email');

        if (! $email) {
            return response()->json(['message' => 'No recipient email provided.'], 422);
        }

        Mail::to($email)->send(new CustomMailable($template, $this->variablesFromRequest($request)));

        return response()->json(['message' => 'Test email sent to ' . $email]);
    }

    /**
     * Build an unsaved template from the builder's current (draft) state.
     */
    protected function draftFromRequest(Request $request): MailTemplate
    {
        $content = $request->input('content', '');
        if (! empty($request->structure)) {
            $content = $this->builder->generateHtmlFromStructure($request->structure, []);
        }

        return new MailTemplate([
            'name' => 'Draft',
            'subject' => $request->input('subject', 'Test email'),
            'content' => $content,
            'structure' => $request->input('structure'),
            'styles' => $request->input('styles'),
            'images' => $request->input('images'),
            'layout' => $request->input('layout', 'default'),
        ]);
    }

    protected function variablesFromRequest(Request $request): array
    {
        // Clients send either {variables: {...}} or the variables as flat fields.
        $variables = $request->has('variables')
            ? (array) $request->input('variables')
            : $request->except(['email', 'emails', 'subject', 'content', 'structure', 'styles', 'images', 'layout']);

        return array_merge(config('mail-customizer.default_variables', []), $variables);
    }
    
    public function sendCustomEmail(Request $request, $id)
    {
        $template = MailTemplate::findOrFail($id);
        $request->validate(['emails' => 'required|string']);
        $data = array_merge(config('mail-customizer.default_variables', []), $request->except('emails'));
        $emails = explode(',', $request->emails);
        
        foreach ($emails as $email) {
            Mail::to(trim($email))->send(new CustomMailable($template, $data));
        }
        
        return response()->json(['message' => 'Emails sent successfully']);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:' . config('mail-customizer.images.max_upload_size', 2048),
        ]);
        
        try {
            $disk = config('mail-customizer.images.default_storage', 'public');
            $path = $request->file('image')->store(
                config('mail-customizer.images.storage_path', 'mail-templates/images'),
                $disk
            );
            
            $url = Storage::disk($disk)->url($path);
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteImage(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);
        
        try {
            $disk = config('mail-customizer.images.default_storage', 'public');
            $root = trim(config('mail-customizer.images.storage_path', 'mail-templates/images'), '/');
            $path = $request->path;

            // Only allow deleting files inside the package's own upload folder.
            if (str_contains($path, '..') || ! str_starts_with(ltrim($path, '/'), $root . '/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image path'
                ], 422);
            }

            Storage::disk($disk)->delete(ltrim($path, '/'));
            
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }
}