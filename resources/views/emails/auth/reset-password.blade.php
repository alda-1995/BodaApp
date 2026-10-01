<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        .body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
        .button { 
            display: inline-block; 
            padding: 12px 24px; 
            background-color: #000; 
            color: #fff !important; 
            text-decoration: none; 
            border-radius: 6px;
            font-weight: bold;
        }
        .footer { font-size: 12px; color: #888; margin-top: 30px; }
    </style>
</head>
<body class="body">
    <div class="container">
        <h2>Hola, {{ $name }}</h2>
        <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>{{ config('app.name') }}</strong>.</p>
        <p>Puedes cambiar tu contraseña haciendo clic en el siguiente botón:</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" class="button">Restablecer mi contraseña</a>
        </div>
        
        <p>Este enlace expirará en 60 minutos. Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
        
        <div class="footer">
            Si tienes problemas con el botón, copia y pega esta URL en tu navegador:<br>
            <a href="{{ $url }}">{{ $url }}</a>
        </div>
    </div>
</body>
</html>