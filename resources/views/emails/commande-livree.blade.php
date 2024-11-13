<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .content {
            padding: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Votre commande a été livrée</h1>
        </div>

        <div class="content">
            <p>Bonjour {{ $commande->user->name }},</p>

            <p>Nous vous confirmons que votre commande a été livrée avec succès.</p>

            <h3>Détails de la commande :</h3>
            <ul>
                <li>Numéro de commande : #{{ $commande->id }}</li>
                <li>Date de commande : {{ $commande->date }}</li>
                <li>Montant total : {{ number_format($commande->somme, 0) }} CF</li>
            </ul>

            <p>Nous vous remercions de votre confiance et espérons vous revoir bientôt !</p>
        </div>

        <div class="footer">
            <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
        </div>
    </div>
</body>
</html>
