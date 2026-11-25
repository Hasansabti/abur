<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // رابط إعادة التعيين (عدّل اسم الراوت وفق ما هو معرف لديك في routes/web.php)
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور - منصة عبور 🔐')
            ->greeting('أهلاً بك 👋')
            ->line('تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك في منصة عبور.')
            ->action('إعادة تعيين كلمة المرور', $url)
            ->line('ملاحظة: هذا الرابط صلاحيته 60 دقيقة فقط.')
            ->line('إذا لم تقم بطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذه الرسالة وأمان حسابك بوضع جيد.')
            ->salutation('مع تحيات، فريق منصة عبور 🚀');
    }
}