<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PostRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $analysis
     */
    public function __construct(
        private readonly Post $post,
        private readonly array $analysis,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu publicación fue retirada')
            ->line('Tu publicación fue retirada por incumplir las políticas de contenido.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $reasons = is_array($this->analysis['reasons'] ?? null) ? $this->analysis['reasons'] : [];
        $labels = [];
        if (($reasons['profanity'] ?? false) === true) {
            $labels[] = 'lenguaje ofensivo';
        }
        if (($reasons['irrelevant_content'] ?? false) === true) {
            $labels[] = 'contenido fuera del contexto sostenible';
        }
        if (($reasons['prompt_injection'] ?? false) === true) {
            $labels[] = 'intento de manipulación del sistema';
        }

        $reasonText = $labels !== [] ? implode(', ', $labels) : 'incumplimiento de políticas';

        return [
            'title' => 'Publicación retirada automáticamente',
            'body' => 'Tu publicación "'.$this->post->title.'" fue retirada por: '.$reasonText.'.',
            'url' => route('dashboard', [], false),
            'post_id' => $this->post->id,
            'analysis' => $this->analysis,
        ];
    }
}

