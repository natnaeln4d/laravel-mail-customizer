<?php

namespace Natnaeln4d\MailCustomizer\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use Natnaeln4d\MailCustomizer\Mail\CustomMailable;
use Natnaeln4d\MailCustomizer\Models\MailTemplate;
use Natnaeln4d\MailCustomizer\Rendering\Blocks\Block;
use Natnaeln4d\MailCustomizer\Rendering\EmailRenderer;
use Natnaeln4d\MailCustomizer\Tests\TestCase;

class EmailRendererTest extends TestCase
{
    protected function renderer(): EmailRenderer
    {
        return $this->app->make(EmailRenderer::class);
    }

    protected function sample(): array
    {
        return [
            ['type' => 'text', 'content' => '<h2>Hello {{name}}</h2><p>Welcome <a href="https://example.com">aboard</a>.</p>'],
            ['type' => 'image', 'content' => '', 'properties' => ['src' => 'https://example.com/a.png', 'alt' => 'Logo', 'width' => '200px', 'alignment' => 'center']],
            ['type' => 'button', 'properties' => ['text' => 'Get started', 'href' => 'https://example.com/go'], 'styles' => ['background-color' => '#ff0000', 'border-radius' => '8px']],
            ['type' => 'divider'],
            ['type' => 'spacer', 'properties' => ['height' => '30']],
        ];
    }

    public function test_output_is_table_based_with_inline_styles()
    {
        $html = $this->renderer()->render($this->sample());

        $this->assertStringContainsString('<table role="presentation"', $html);
        $this->assertStringContainsString('width="600"', $html);
        $this->assertStringContainsString('bgcolor="#ff0000"', $html);
        $this->assertStringContainsString('<!--[if mso]><v:roundrect', $html);
        $this->assertStringContainsString('width="200"', $html);
        $this->assertStringContainsString('height="30"', $html);

        // Things that break in email clients must not appear in the body.
        $body = substr($html, strpos($html, '<body'));
        $this->assertStringNotContainsString('<div', $body);
        $this->assertStringNotContainsString('float:', $body);
        $this->assertStringNotContainsString('<hr', $body);
        $this->assertStringNotContainsString('<script', $body);
    }

    public function test_placeholders_survive_for_the_compiler_to_fill()
    {
        $this->assertStringContainsString('Hello {{name}}', $this->renderer()->render($this->sample()));
    }

    public function test_text_is_sanitised()
    {
        $html = $this->renderer()->render([[
            'type' => 'text',
            'content' => '<p onclick="x()" style="x">Hi</p><script>alert(1)</script><a href="javascript:alert(1)">bad</a><iframe src="//evil"></iframe>',
        ]]);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('href="#"', $html);
    }

    public function test_style_values_cannot_break_out_of_attributes()
    {
        $html = $this->renderer()->render([[
            'type' => 'text', 'content' => 'x',
            'styles' => ['color' => 'red" onmouseover="alert(1)', 'padding' => '1px;} <b>'],
        ]]);

        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function test_button_reads_legacy_anchor_content()
    {
        $html = $this->renderer()->render([[
            'type' => 'button', 'content' => '<a href="https://old.test/x" style="...">Old label</a>',
        ]]);

        $this->assertStringContainsString('Old label', $html);
        $this->assertStringContainsString('https://old.test/x', $html);
    }

    public function test_image_without_src_renders_nothing_and_unknown_types_degrade_to_text()
    {
        $r = $this->renderer();

        $this->assertSame('', $r->renderElement(['type' => 'image', 'properties' => ['src' => '']]));
        $this->assertStringContainsString('Legacy', $r->renderElement(['type' => 'mystery', 'content' => '<p>Legacy</p>']));
    }

    public function test_image_width_is_capped_to_container()
    {
        $html = $this->renderer()->renderElement(['type' => 'image', 'properties' => ['src' => 'https://x.test/a.png', 'width' => '2000px']]);

        $this->assertStringContainsString('width="600"', $html);
    }

    public function test_preheader_and_plain_text()
    {
        $r = $this->renderer();

        $this->assertStringContainsString('Inbox preview line', $r->render([], ['preheader' => 'Inbox preview line']));

        $text = $r->toPlainText($this->sample());
        $this->assertStringContainsString('Hello {{name}}', $text);
        $this->assertStringContainsString('Get started: https://example.com/go', $text);
        $this->assertStringNotContainsString('<', $text);
    }

    public function test_columns_render_nested_blocks()
    {
        $html = $this->renderer()->renderElement(['type' => 'columns', 'properties' => ['columns' => [
            [['type' => 'text', 'content' => '<p>Left</p>']],
            [['type' => 'text', 'content' => '<p>Right</p>']],
        ]]]);

        $this->assertStringContainsString('Left', $html);
        $this->assertStringContainsString('Right', $html);
        $this->assertSame(2, substr_count($html, 'class="mc-stack"'));
    }

    public function test_custom_blocks_can_be_registered()
    {
        $r = $this->renderer();
        $r->registerBlock('hello', HelloBlock::class);

        $this->assertStringContainsString('custom!', $r->renderElement(['type' => 'hello']));

        $this->expectException(InvalidArgumentException::class);
        $r->registerBlock('bad', \stdClass::class);
    }

    public function test_saving_a_structure_stores_rendered_email_html()
    {
        $id = $this->postJson('/api/mail-customizer/templates', [
            'name' => 'W', 'subject' => 'S', 'content' => 'ignored', 'structure' => $this->sample(),
        ])->assertCreated()->json('id');

        $preview = $this->postJson("/api/mail-customizer/templates/{$id}/preview", ['variables' => ['name' => 'Zed']])
            ->assertOk()->json('preview');

        $this->assertStringContainsString('Hello Zed', $preview);
        $this->assertStringContainsString('<table role="presentation"', $preview);
    }

    public function test_builder_page_and_blade_component_render()
    {
        $this->get('/mail-customizer')->assertOk()->assertSee('data-mail-builder', false);
        $this->get('/mail-customizer/5')->assertOk()->assertSee('data-template-id="5"', false);

        $html = Blade::render('<x-mail-customizer::builder :template-id="3" :variables="$vars" />', ['vars' => ['name' => 'A']]);
        $this->assertStringContainsString('data-template-id="3"', $html);
        $this->assertStringContainsString('mail-customizer.js', $html);
    }

    public function test_theme_comes_from_config_and_can_be_overridden_per_component()
    {
        config()->set('mail-customizer.theme.preset', 'goku');
        config()->set('mail-customizer.theme.brand', 'Acme Mail');

        $default = Blade::render('<x-mail-customizer::builder />');
        $theme = $this->themeFrom($default);
        $this->assertSame('goku', $theme['preset']);
        $this->assertSame('Acme Mail', $theme['brand']);
        $this->assertSame('auto', $theme['mode']);          // package default survives

        $custom = Blade::render('<x-mail-customizer::builder :theme="$t" :ui-fonts="false" />', [
            't' => ['mode' => 'inherit', 'accent' => '#7c3aed', 'font' => 'inherit'],
        ]);
        $theme = $this->themeFrom($custom);
        $this->assertSame('inherit', $theme['mode']);
        $this->assertSame('#7c3aed', $theme['accent']);
        $this->assertSame('goku', $theme['preset']);        // merged over config, not replaced
        $this->assertStringContainsString('data-ui-fonts="false"', $custom);

        // The built-in page gets the same config.
        $page = $this->get('/mail-customizer')->assertOk()->getContent();
        $this->assertSame('Acme Mail', $this->themeFrom($page)['brand']);
    }

    protected function themeFrom(string $html): array
    {
        $this->assertSame(1, preg_match("/data-theme='([^']*)'/", $html, $m), 'data-theme attribute missing');

        return json_decode(html_entity_decode($m[1]), true);
    }

    public function test_model_helpers()
    {
        MailTemplate::create(['name' => 'Welcome', 'subject' => 'Hi', 'content' => '<p>x</p>']);

        $mailable = MailTemplate::named('Welcome')->mailable(['name' => 'A']);
        $this->assertInstanceOf(CustomMailable::class, $mailable);
    }
}

class HelloBlock extends Block
{
    public function render(array $element): string
    {
        return '<tr><td>custom!</td></tr>';
    }
}
