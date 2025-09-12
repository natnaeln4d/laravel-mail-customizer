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
        $data = $request->all();
        
        $compiled = $this->compiler->compile($template, $data);
        
        return response()->json(['preview' => $compiled]);
    }
    
    public function sendTestEmail(Request $request, $id)
    {
        $template = MailTemplate::findOrFail($id);
        $email = $request->email ?? auth()->user()->email;
        
        Mail::to($email)->send(new CustomMailable($template, $request->all()));
        
        return response()->json(['message' => 'Test email sent to ' . $email]);
    }
    
    public function sendCustomEmail(Request $request, $id)
    {
        $template = MailTemplate::findOrFail($id);
        $data = $request->except('emails');
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
            $path = $request->file('image')->store(
                config('mail-customizer.images.storage_path', 'mail-templates/images'),
                config('mail-customizer.images.default_storage', 'public')
            );
            
            $url = Storage::url($path);
            
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
            Storage::delete($request->path);
            
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