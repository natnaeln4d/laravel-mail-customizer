<?php

namespace Natnaeln4d\MailCustomizer\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Natnaeln4d\MailCustomizer\Mail\CustomMailable;
use Natnaeln4d\MailCustomizer\Models\MailTemplate;
use Natnaeln4d\MailCustomizer\Tests\TestCase;

class TemplateApiTest extends TestCase
{
    protected $prefix = '/api/mail-customizer';

    public function test_builder_elements_are_served()
    {
        $this->getJson("{$this->prefix}/builder/elements")
            ->assertOk()
            ->assertJsonStructure(['elements' => ['text', 'image', 'button']]);
    }

    public function test_template_crud_round_trips_structure()
    {
        $structure = [['id' => 'a', 'type' => 'text', 'content' => '<p>Hi {{name}}</p>']];

        $id = $this->postJson("{$this->prefix}/templates", [
            'name' => 'Welcome',
            'subject' => 'Hello',
            'content' => '<p>x</p>',
            'structure' => $structure,
        ])->assertCreated()->json('id');

        $this->getJson("{$this->prefix}/templates/{$id}")
            ->assertOk()
            ->assertJsonPath('name', 'Welcome')
            ->assertJsonPath('structure.0.type', 'text');
    }

    public function test_preview_replaces_and_escapes_variables()
    {
        $template = MailTemplate::create([
            'name' => 'T', 'subject' => 'S', 'content' => '<p>Hi {{ name }} / {{name}}</p>',
        ]);

        $this->postJson("{$this->prefix}/templates/{$template->id}/preview", [
            'variables' => ['name' => '<b>Bob</b>'],
        ])->assertOk()->assertJsonPath('preview', '<p>Hi &lt;b&gt;Bob&lt;/b&gt; / &lt;b&gt;Bob&lt;/b&gt;</p>');
    }

    public function test_draft_preview_works_without_a_saved_template()
    {
        $this->postJson("{$this->prefix}/templates/preview", [
            'content' => '<p>Hello {{name}}</p>',
            'variables' => ['name' => 'Ann'],
        ])->assertOk()->assertJsonPath('preview', '<p>Hello Ann</p>');
    }

    public function test_send_test_for_saved_template_and_draft()
    {
        Mail::fake();

        $template = MailTemplate::create(['name' => 'T', 'subject' => 'S', 'content' => '<p>Hi</p>']);

        $this->postJson("{$this->prefix}/templates/{$template->id}/send-test", ['email' => 'a@example.com'])
            ->assertOk();
        $this->postJson("{$this->prefix}/templates/send-test", [
            'email' => 'b@example.com', 'subject' => 'Draft', 'content' => '<p>Draft</p>',
        ])->assertOk();

        Mail::assertSent(CustomMailable::class, 2);
        Mail::assertSent(CustomMailable::class, fn ($m) => $m->hasTo('b@example.com'));
    }

    public function test_routes_honour_configured_middleware()
    {
        config()->set('mail-customizer.middleware', ['web', 'auth']);
        $this->refreshApplicationRoutes();

        $this->getJson("{$this->prefix}/templates")->assertUnauthorized();
    }

    public function test_delete_image_rejects_paths_outside_upload_folder()
    {
        $this->deleteJson("{$this->prefix}/delete-image", ['path' => '../.env'])->assertStatus(422);
        $this->deleteJson("{$this->prefix}/delete-image", ['path' => 'other/file.png'])->assertStatus(422);
    }

    protected function refreshApplicationRoutes()
    {
        // Re-register package routes with the new middleware config.
        $this->app['router']->setRoutes(new \Illuminate\Routing\RouteCollection());
        (new \Natnaeln4d\MailCustomizer\MailCustomizerServiceProvider($this->app))->boot();
        $this->app['router']->getRoutes()->refreshNameLookups();
    }
}
