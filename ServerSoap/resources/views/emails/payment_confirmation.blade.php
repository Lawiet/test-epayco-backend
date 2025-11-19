<!DOCTYPE html>
<html>
<head>
    <title>Confirmación de Pago</title>
</head>
<body>
<h1>Hola {{ $clientName }},</h1>
<p>Has iniciado una compra por valor de: ${{ number_format($amount, 2) }}</p>
<p>Usa el siguiente código de 6 dígitos para confirmar tu pago:</p>
<h2>{{ $token }}</h2>
<p>Este código expira en 5 minutos.</p>
<p>Gracias por usar nuestra billetera virtual.</p>
</body>
</html>
