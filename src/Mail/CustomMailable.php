<?php

namespace Natnaeln4d\MailCustomizer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Natnaeln4d\MailCustomizer\Models\MailTemplate;
use Natnaeln4d\MailCustomizer\Services\TemplateCompiler;

class CustomMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The mail template instance.
     *
     * @var MailTemplate
     */
    public $template;

    /**
     * The data for template variables.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new message instance.
     *
     * @param MailTemplate $template
     * @param array $data
     * @return void
     */
    public function __construct(MailTemplate $template, array $data = [])
    {
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->template->subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        $compiler = app(TemplateCompiler::class);
        $content = $compiler->compile($this->template, $this->data);

        return new Content(
            htmlString: $content,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }

    /**
     * Build the message (alternative method for Laravel versions < 9).
     *
     * @return $this
     */
    public function build()
    {
        $compiler = app(TemplateCompiler::class);
        $content = $compiler->compile($this->template, $this->data);

        return $this->subject($this->template->subject)
                    ->html($content);
    }
}