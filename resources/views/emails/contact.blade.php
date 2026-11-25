<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8fafc; padding: 20px; color: #334155; }
        .card { background: #ffffff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; max-width: 600px; margin: 0 auto; }
        .header { font-size: 18px; font-weight: bold; color: #4f46e5; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .field { margin-bottom: 12px; }
        .label { font-weight: bold; color: #64748b; font-size: 13px; }
        .value { font-size: 15px; font-weight: bold; color: #0f172a; }
        .message-box { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 15px; white-space: pre-line; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">📩 رسالة تواصل جديدة - منصة عبور</div>
        
        <div class="field">
            <span class="label">الاسم:</span>
            <div class="value">{{ $data['user_name'] }}</div>
        </div>
        
        <div class="field">
            <span class="label">البريد الإلكتروني:</span>
            <div class="value">{{ $data['user_email'] }}</div>
        </div>

        <div class="field">
            <span class="label">نوع الاستفسار:</span>
            <div class="value">{{ $data['subject_label'] }}</div>
        </div>

        <div class="field">
            <span class="label">نص الرسالة:</span>
            <div class="message-box">{{ $data['message_content'] }}</div>
        </div>
    </div>
</body>
</html>