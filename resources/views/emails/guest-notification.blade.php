<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notification->subject }}</title>
</head>

<body style="margin:0;padding:0;background-color:#FAFAFA;font-family:Arial,Helvetica,sans-serif;color:#1A1A1A;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FAFAFA;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:560px;background-color:#FFFFFF;border:1px solid #EBEBEB;border-radius:12px;padding:32px;">
                    <tr>
                        <td style="font-size:16px;line-height:1.6;white-space:pre-line;">{{ $notification->body }}</td>
                    </tr>
                    <tr>
                        <td style="padding-top:28px;" align="center">
                            <a href="{{ $notification->url }}"
                                style="display:inline-block;background-color:#29241C;color:#FFFFFF;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;">
                                Ver invitación
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-top:20px;font-size:12px;color:#8C8C8C;" align="center">
                            Si el botón no funciona, copia esta liga:<br>
                            <a href="{{ $notification->url }}" style="color:#8C8C8C;">{{ $notification->url }}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
