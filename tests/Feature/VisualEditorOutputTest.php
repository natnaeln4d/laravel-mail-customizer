<?php

namespace Natnaeln4d\MailCustomizer\Tests\Feature;

use Natnaeln4d\MailCustomizer\Rendering\EmailRenderer;
use Natnaeln4d\MailCustomizer\Tests\TestCase;

/**
 * The visual (GrapesJS) editor sends html + css. The renderer must turn that
 * into something Outlook/Gmail can display.
 */
class VisualEditorOutputTest extends TestCase
{
    protected function render(string $html, string $css = '', array $options = []): string
    {
        return $this->app->make(EmailRenderer::class)->renderHtml($html, $css, $options);
    }

    public function test_css_rules_are_inlined()
    {
        $out = $this->render('<p id="a1" class="x">Hi</p>', '#a1{color:#ff0000;} .x{font-size:20px;}');

        $this->assertMatchesRegularExpression('/<p[^>]*style="[^"]*color:\s*#ff0000/i', $out);
        $this->assertMatchesRegularExpression('/<p[^>]*style="[^"]*font-size:\s*20px/i', $out);
    }

    public function test_div_padding_and_background_move_to_a_table_cell()
    {
        $out = $this->render(
            '<div id="s" data-mc="section"><p>Inside</p></div>',
            '#s{padding:30px;background-color:#6366f1;}'
        );

        // Wrapped in <table><tr><td bgcolor style="padding…"> and removed from the div.
        $this->assertMatchesRegularExpression('/<td[^>]*bgcolor="#6366f1"/i', $out);
        $this->assertMatchesRegularExpression('/<td[^>]*style="[^"]*padding:\s*30px/i', $out);
        $this->assertMatchesRegularExpression('/<div(?![^>]*padding)[^>]*>/i', substr($out, strpos($out, '<body')));
        $this->assertStringContainsString('Inside', $out);
    }

    public function test_background_image_gets_a_background_attribute_for_outlook()
    {
        $out = $this->render(
            '<div id="s" data-mc="section">x</div>',
            '#s{background-image:linear-gradient(rgba(0,0,0,0.4),rgba(0,0,0,0.4)),url("https://cdn.test/hero.jpg");background-size:cover;padding:40px;}'
        );

        $this->assertStringContainsString('background="https://cdn.test/hero.jpg"', $out);
        $this->assertStringContainsString('linear-gradient', $out);   // modern clients keep the overlay/gradient
    }

    public function test_button_becomes_a_bulletproof_table_with_alignment()
    {
        $out = $this->render(
            '<div id="w" style="text-align:center"><a id="b" data-mc="button" href="https://x.test">Go</a></div>',
            '#b{background-color:#111111;color:#ffffff;padding:12px 24px;border-radius:6px;display:inline-block;text-decoration:none;}'
        );

        $this->assertMatchesRegularExpression('/<table[^>]*align="center"/i', $out);
        $this->assertMatchesRegularExpression('/<td[^>]*bgcolor="#111111"/i', $out);
        $this->assertMatchesRegularExpression('/<td[^>]*style="[^"]*border-radius:\s*6px/i', $out);
        $this->assertMatchesRegularExpression('/<a[^>]*href="https:\/\/x\.test"/i', $out);
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*padding/i', $out);
    }

    public function test_spacer_becomes_a_sized_table_cell()
    {
        $out = $this->render('<div id="sp" data-mc="spacer"></div>', '#sp{height:40px;}');

        $this->assertMatchesRegularExpression('/<td[^>]*height="40"/i', $out);
    }

    public function test_images_get_attributes_and_absolute_urls()
    {
        $out = $this->render('<img id="i" src="/storage/a.png">', '#i{width:300px;}');

        $this->assertMatchesRegularExpression('/<img[^>]*src="http[^"]*\/storage\/a\.png"/i', $out);
        $this->assertStringContainsString('width="300"', $out);
        $this->assertStringContainsString('alt=""', $out);
    }

    public function test_image_blocks_without_a_picture_are_left_out()
    {
        // The exact placeholder GrapesJS puts on a freshly dropped, still-empty image.
        $placeholder = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="100" viewBox="0 0 24 24"><path d="M8.5 13.5l2.5 3 3.5-4.5 4.5 6H5m16 1V5a2 2 0 0 0-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2z"></path></svg>'
        );

        $out = $this->render(
            '<p>Before</p><div data-mc="image-wrap" id="w"><img data-mc="image" src="' . $placeholder . '"></div>'
            . '<div data-mc="image-wrap"><img data-mc="image" src="https://cdn.test/real.png"></div><p>After</p>',
            '#w{padding:10px 20px;}'
        );

        $this->assertStringNotContainsString('PHN2Zy', $out);        // placeholder gone
        $this->assertStringContainsString('https://cdn.test/real.png', $out);
        $this->assertStringContainsString('Before', $out);
        $this->assertStringContainsString('After', $out);
        $this->assertSame(1, substr_count($out, '<img'));
    }

    public function test_media_queries_are_kept_and_forced_with_important()
    {
        $out = $this->render('<p id="m1">x</p>', '#m1{font-size:20px;} @media (max-width:480px){#m1{font-size:14px;}}');

        $this->assertMatchesRegularExpression('/@media \(max-width:480px\)\{#m1\{font-size:14px !important\}\}/', $out);
        $this->assertStringContainsString('id="m1"', $out);                 // id kept because a media rule needs it
    }

    public function test_editor_only_attributes_are_stripped()
    {
        $out = $this->render('<p id="gone" class="gjs-selected foo mc-stack" data-gjs-type="text" draggable="true" contenteditable="true">x</p>');

        $this->assertStringNotContainsString('id="gone"', $out);
        $this->assertStringNotContainsString('gjs-', $out);
        $this->assertStringNotContainsString('data-gjs', $out);
        $this->assertStringNotContainsString('contenteditable', $out);
        $this->assertStringContainsString('class="mc-stack"', $out);
    }

    public function test_dangerous_markup_is_removed()
    {
        $out = $this->render(
            '<p onclick="x()">a</p><script>alert(1)</script><iframe src="//e"></iframe><a href="javascript:alert(1)">b</a>',
            '@import url(//evil.test/x.css); p{background:url(javascript:alert(1))}'
        );
        $body = substr($out, strpos($out, '<body'));

        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('<iframe', $body);
        $this->assertStringNotContainsString('javascript:', $out);
        $this->assertStringNotContainsString('evil.test', $out);
    }

    public function test_fonts_variables_and_unicode_survive()
    {
        $out = $this->render('<p>Héllo {{name}} — ✓</p>', '', ['fonts' => ['Open Sans', 'bad<font>']]);

        $this->assertStringContainsString('Héllo {{name}} — ✓', $out);
        $this->assertStringContainsString('fonts.googleapis.com/css2?family=Open+Sans:wght@400;700', $out);
        $this->assertStringNotContainsString('bad<font>', $out);
    }

    public function test_api_stores_rendered_html_and_project_data_and_round_trips()
    {
        $project = ['pages' => [['frames' => [['component' => ['type' => 'wrapper']]]]], 'styles' => []];

        $id = $this->postJson('/api/mail-customizer/templates', [
            'name' => 'Visual', 'subject' => 'Hi',
            'html' => '<div id="s" data-mc="section"><p id="t">Hello {{name}}</p></div>',
            'css' => '#s{padding:20px;background-color:#eeeeee;} #t{color:#123456;}',
            'structure' => $project,
        ])->assertCreated()->json('id');

        $this->getJson("/api/mail-customizer/templates/{$id}")
            ->assertOk()
            ->assertJsonPath('structure.pages.0.frames.0.component.type', 'wrapper');

        $preview = $this->postJson("/api/mail-customizer/templates/{$id}/preview", ['variables' => ['name' => 'Zed']])
            ->assertOk()->json('preview');

        $this->assertStringContainsString('Hello Zed', $preview);
        $this->assertStringContainsString('bgcolor="#eeeeee"', $preview);
        $this->assertStringContainsString('<table role="presentation" class="mc-container"', $preview);
    }

    /**
     * fixtures/editor-output.json is the genuine html+css the builder produced in a
     * browser (header, text, spacer, button, divider, footer and a hero with an
     * image + dark overlay + gradient background).
     */
    public function test_real_editor_output_becomes_email_safe_html()
    {
        $fixture = json_decode(file_get_contents(__DIR__ . '/../fixtures/editor-output.json'), true);
        $out = $this->render($fixture['html'], $fixture['css']);
        $body = substr($out, strpos($out, '<body'));

        if ($path = getenv('DUMP_EMAIL_TO')) {
            file_put_contents($path, $out);
        }

        // Hero: padding/background moved to a table cell, with image + fallback colour for Outlook.
        $this->assertMatchesRegularExpression('/<td[^>]*style="[^"]*padding:\s*56px 32px/i', $out);
        $this->assertMatchesRegularExpression('/<td[^>]*bgcolor="#6366f1"/i', $out);
        $this->assertStringContainsString('background="data:image/svg+xml', $out);
        $this->assertStringContainsString('linear-gradient(rgba(0, 0, 0, 0.4)', $out);

        // Both buttons became bulletproof table cells with their own colours.
        $this->assertMatchesRegularExpression('/<td[^>]*bgcolor="#6366f1"[^>]*>\s*<a[^>]*>Open dashboard/i', $out);
        $this->assertMatchesRegularExpression('/<td[^>]*bgcolor="#ffffff"[^>]*>\s*<a[^>]*>Get started/i', $out);
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*padding/i', $body);

        // Spacer became a sized cell; no editor leftovers; variables intact.
        $this->assertMatchesRegularExpression('/<td[^>]*height="30"/i', $out);
        $this->assertStringNotContainsString('data-mc', $body);
        $this->assertStringNotContainsString('id="i', $body);
        $this->assertStringContainsString('Hello {{name}} 👋', $out);
        $this->assertStringContainsString('{{company}}', $out);

        // Nothing the user styled with padding/background is left on a <div>/<p>/<h2>/<a>.
        $this->assertDoesNotMatchRegularExpression('/<(div|p|h2)[^>]*style="[^"]*(padding|background)/i', $body);
    }

    public function test_draft_preview_accepts_editor_output()
    {
        $this->postJson('/api/mail-customizer/templates/preview', [
            'html' => '<p>Draft {{name}}</p>', 'css' => '', 'variables' => ['name' => 'Bo'],
        ])->assertOk()->assertJsonPath('preview', fn ($p) => str_contains($p, 'Draft Bo'));
    }
}
