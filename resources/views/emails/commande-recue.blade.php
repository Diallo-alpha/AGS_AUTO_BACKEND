<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nouvelle commande reçue</title>
</head>
<body>
    <h1>Nouvelle commande reçue</h1>
    <p>Bonjour,</p>
    <p>Une nouvelle commande a été passée sur notre site web :</p>
    <table>
        <tr>
            <th>Commande n°</th>
            <th>Montant total</th>
            <th>Date</th>
            <th>Statut</th>
        </tr>
        <tr>
            <td>{{ $commande->id }}</td>
            <td>{{ $commande->somme }} Francs</td>
            <td>{{ $commande->date }}</td>
            <td>{{ $commande->status }}</td>
        </tr>
    </table>
    <p>Vous pouvez consulter les détails de cette commande dans l'application.</p>
    <p>Cordialement,</p>
    <p>L'équipe de {{ config('app.name') }}</p>
</body>
</html>
