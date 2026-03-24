<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmação de Entrega</title>
</head>
<body>
    <h2>MotorTech - Confirmação de Entrega</h2>
    <p>A OS <strong>#{{ $osId }}</strong> foi finalizada. Para confirmar a retirada/entrega, clique no link abaixo:</p>
    <p><a href="{{ $confirmUrl }}">Confirmar entrega da OS #{{ $osId }}</a></p>
    <p>Se você não solicitou este email, pode ignorar.</p>
</body>
</html>
