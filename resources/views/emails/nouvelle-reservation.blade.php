<!DOCTYPE html>
<html>
<head>
    <title>Nouvelle demande de réservation</title>
</head>
<body>
    <h1>Nouvelle demande de réservation</h1>
    <p>Bonjour {{ $partenaire->nom_partenaire }},</p>
    <p>Vous avez reçu une nouvelle demande de réservation pour le service "{{ $service->titre }}".</p>

    <h2>Détails de la demande :</h2>
    <ul>
        <li>Client : {{ $user->nom_complet }}</li>
        <li>Email du client : {{ $user->email }}</li>
        <li>Date souhaitée : {{ $reservation->date_reservation->format('d/m/Y H:i') }}</li>
        @if($reservation->message)
            <li>Message du client : {{ $reservation->message }}</li>
        @endif
    </ul>

    <p>Vous pouvez contacter directement le client par email pour confirmer cette réservation.</p>
</body>
</html>
