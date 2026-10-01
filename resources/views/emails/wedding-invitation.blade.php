<!DOCTYPE html>
<html lang="es" style="margin: 0; padding: 0;">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invitación de Boda - Elena y Alfonso</title>
    <style>
        /* Estilos generales para reset y tipografía */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f6f6f6;
        }

        /* Contenedor principal para centrar */
        .container {
            width: 100%;
            max-width: 440px;
            margin: 60px auto;
            border-spacing: 0;
            border-collapse: collapse;
        }

        /* Estilo para el fondo (simulando la imagen en blanco y negro) */
        .bg-main {
            background-color: rgba(0, 0, 0, 0.7);
            /* Oscurece un poco la imagen de fondo */
            background-image: url('https://tamira.app/images/assets-travel/fondo-mailing-full.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .bg-card {
            background-image: url('https://tamira.app/images/assets-travel/fondo-mailing-tamira.jpg');
            background-size: cover;
            background-position: top;
            background-repeat: no-repeat;
        }

        /* Estilo del recuadro principal (el papel de la invitación) */
        .invitation-box {
            background-color: #fcfcfc;
            border: 1px solid #eee;
            padding: 140px 30px 0px 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Título */
        .title {
            color: #4b3e8e;
            font-size: 28px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 30px;
            text-align: center;
        }

        /* Imagen de los novios */
        .couple-image {
            width: 100%;
            height: auto;
            display: block;
            margin: 0 auto 30px;
        }

        /* Texto de saludo */
        .greeting {
            color: #8B8494;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 15px;
        }

        /* Párrafo del cuerpo */
        .body-text {
            color: #8B8494;
            font-size: 16px;
            margin-bottom: 30px;
        }

        /* Botón de confirmación */
        .button {
            display: inline-block;
            background-color: #EEEBE9;
            color: #4D3D7E;
            padding: 14px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center">
                <table role="presentation" width="600px" cellspacing="0" cellpadding="0" border="0" class="bg-main">
                    <tr>
                        <td align="center">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                class="container">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0" class="invitation-box bg-card">
                                            <tr>
                                                <td style="padding: 40px 0 16px 0;">
                                                    <table role="presentation" width="100%" cellspacing="0"
                                                        cellpadding="0" border="0" class="">
                                                        <tr>
                                                            <td style="padding: 0 20px;">
                                                                <p class="title">¡Estás invitado a nuestra boda!</p>

                                                                <img src="https://tamira.app/images/assets-travel/eclipse-tamira.png"
                                                                    alt="Pareja de novios" class="couple-image"
                                                                    style="max-width: 270px;">

                                                                <p class="greeting">
                                                                    Querido/a {{ $guest->name }},
                                                                </p>

                                                                <p class="body-text">
                                                                    Será un honor y una alegría compartir este momento tan importante contigo.Tu presencia hará de este día algo aún más memorable.
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding: 0 30px 60px 30px;">
                                                    <table role="presentation" border="0" cellpadding="0"
                                                        cellspacing="0" align="center">
                                                        <td align="center">
                                                            <a href="{{ $url}}" class="button"
                                                                style="display: inline-block; background-color: #EEEBE9; color: #4D3D7E; padding: 14px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px;">
                                                                Confirmar asistencia
                                                            </a>
                                                        </td>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>

</html>