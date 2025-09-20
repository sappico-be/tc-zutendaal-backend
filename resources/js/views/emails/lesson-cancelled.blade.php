<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { background: #f4f4f4; padding: 20px; margin: 20px 0; }
        .info-row { margin: 10px 0; }
        .alert { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; color: #777; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Les Geannuleerd</h1>
        </div>
        
        <p>Beste {{ $user->name }},</p>
        
        <div class="alert">
            <strong>Let op:</strong> De volgende tennisles is GEANNULEERD
        </div>
        
        <div class="content">
            <div class="info-row"><strong>Groep:</strong> {{ $lesson->group->name }}</div>
            <div class="info-row"><strong>Datum:</strong> {{ \Carbon\Carbon::parse($lesson->lesson_date)->format('l d F Y') }}</div>
            <div class="info-row"><strong>Tijd:</strong> {{ $lesson->start_time }} - {{ $lesson->end_time }}</div>
            <div class="info-row"><strong>Locatie:</strong> {{ $lesson->location->name ?? '-' }}</div>
        </div>
        
        @if($reason)
        <p><strong>Reden:</strong> {{ $reason }}</p>
        @endif
        
        <p>We informeren je zodra er een nieuwe datum bekend is.</p>
        
        <p>Onze excuses voor het ongemak.</p>
        
        <div class="footer">
            <p>TC Zutendaal | Tennislaan 1, 3690 Zutendaal</p>
            <p>Deze email is automatisch verzonden. Voor vragen kun je contact opnemen met de club.</p>
        </div>
    </div>
</body>
</html>
