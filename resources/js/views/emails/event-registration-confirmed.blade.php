<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #666CFF;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #666CFF;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TC Zutendaal</h1>
        <p>Bevestiging Inschrijving</p>
    </div>
    
    <div class="content">
        <h2>Beste {{ $registration->user->name }},</h2>
        
        <p>Bedankt voor je inschrijving! We hebben je inschrijving voor het volgende evenement ontvangen:</p>
        
        <div class="info-box">
            <h3>{{ $registration->event->title }}</h3>
            
            <div class="info-row">
                <strong>Datum:</strong>
                <span>{{ $registration->event->start_date->format('d/m/Y H:i') }}</span>
            </div>
            
            <div class="info-row">
                <strong>Locatie:</strong>
                <span>{{ $registration->event->location ?? 'TC Zutendaal' }}</span>
            </div>
            
            <div class="info-row">
                <strong>Status:</strong>
                <span>{{ $registration->status === 'confirmed' ? 'Bevestigd' : 'In afwachting' }}</span>
            </div>
            
            @if($registration->payment_status === 'paid')
            <div class="info-row">
                <strong>Betaling:</strong>
                <span>€ {{ number_format($registration->amount_paid, 2, ',', '.') }} - Betaald</span>
            </div>
            @endif
        </div>
        
        @if($registration->event->description)
        <p><strong>Over dit evenement:</strong></p>
        <p>{{ $registration->event->description }}</p>
        @endif
        
        <p>Tot ziens op het evenement!</p>
        
        <p>
            Met sportieve groeten,<br>
            TC Zutendaal
        </p>
    </div>
    
    <div class="footer">
        <p>Tennis Club Zutendaal | info@tczutendaal.be</p>
        <p>Dit is een automatisch gegenereerde email.</p>
    </div>
</body>
</html>
