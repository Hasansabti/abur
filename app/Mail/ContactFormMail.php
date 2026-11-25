<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('رسالة جديدة من الموقع: ' . $this->data['subject_label'])
                    ->replyTo($this->data['user_email'], $this->data['user_name'])
                    ->view('emails.contact');
    }
}