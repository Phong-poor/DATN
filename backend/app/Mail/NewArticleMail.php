<?php

namespace App\Mail;

use App\Models\News;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewArticleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public News $article,
        public string $subscriberEmail
    ) {}

    public function build(): self
    {
        return $this
            ->subject('📰 Bài viết mới: ' . $this->article->tieude)
            ->view('emails.new-article')
            ->with([
                'article'         => $this->article,
                'subscriberEmail' => $this->subscriberEmail,
            ]);
    }
}
