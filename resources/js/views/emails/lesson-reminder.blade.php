<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #7367F0; color: white; padding: 20px; text-align: center; }
        .content { background: #f4f4f4; padding: 20px; margin: 20px 0; }
        .info-row { margin: 10px 0; }
        .button { display: inline-block; background: #7367F0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; color: #777; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Herinnering Tennisles</h1>
        </div>
        
        <p>Beste {{ $user->name }},</p>
        
        <p>Dit is een herinnering voor je tennisles morgen.</p>
        
        <div class="content">
            <div class="info-row"><strong>Groep:</strong> {{ $lesson->group->name }}</div>
            <div class="info-row"><strong>Datum:</strong> {{ \Carbon\Carbon::parse($lesson->lesson_date)->format('l d F Y') }}</div>
            <div class="info-row"><strong>Tijd:</strong> {{ $lesson->start_time }} - {{ $lesson->end_time }}</div>
            <div class="info-row"><strong>Locatie:</strong> {{ $lesson->location->name ?? 'Nog te bepalen' }}</div>
            <div class="info-row"><strong>Trainer:</strong> {{ $lesson->group->trainer->name ?? 'Nog te bepalen' }}</div>
        </div>
        
        @if($customMessage)
        <div style="background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107;">
            <strong>Bericht van de trainer:</strong><br>
            {{ $customMessage }}
        </div>
        @endif
        
        <p>Tot morgen!</p>
        
        <div class="footer">
            <p>TC Zutendaal | Tennislaan 1, 3690 Zutendaal</p>
            <p>Deze email is automatisch verzonden. Reageren is niet mogelijk.</p>
        </div>
    </div>
</body>
</html>
