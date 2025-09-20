<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ffc107; color: #333; padding: 20px; text-align: center; }
        .content { background: #f4f4f4; padding: 20px; margin: 20px 0; }
        .info-row { margin: 10px 0; }
        .changed { background: #fff3cd; padding: 5px; font-weight: bold; }
        .footer { text-align: center; color: #777; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Les Gewijzigd</h1>
        </div>
        
        <p>Beste {{ $user->name }},</p>
        
        <p>Er zijn wijzigingen in je tennisles:</p>
        
        <div class="content">
            <div class="info-row"><strong>Groep:</strong> {{ $lesson->group->name }}</div>
            <div class="info-row @if(in_array('date', $changes)) changed @endif">
                <strong>Datum:</strong> {{ \Carbon\Carbon::parse($lesson->lesson_date)->format('l d F Y') }}
                @if(in_array('date', $changes)) <span style="color: red;">(GEWIJZIGD)</span> @endif
            </div>
            <div class="info-row @if(in_array('time', $changes)) changed @endif">
                <strong>Tijd:</strong> {{ $lesson->start_time }} - {{ $lesson->end_time }}
                @if(in_array('time', $changes)) <span style="color: red;">(GEWIJZIGD)</span> @endif
            </div>
            <div class="info-row @if(in_array('location', $changes)) changed @endif">
                <strong>Locatie:</strong> {{ $lesson->location->name ?? 'Nog te bepalen' }}
                @if(in_array('location', $changes)) <span style="color: red;">(GEWIJZIGD)</span> @endif
            </div>
        </div>
        
        <p>Noteer deze wijzigingen in je agenda!</p>
        
        <div class="footer">
            <p>TC Zutendaal | Tennislaan 1, 3690 Zutendaal</p>
            <p>Deze email is automatisch verzonden. Voor vragen kun je contact opnemen met de club.</p>
        </div>
    </div>
</body>
</html>
