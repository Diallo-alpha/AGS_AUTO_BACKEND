<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificat d'Appréciation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
          @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #ffffff;
        }
        .certificate {
            max-width: 800px;
            margin: 50px auto;
            padding: 40px;
            border: 2px solid #000;
            position: relative;
            background-color: #fff;
        }
        .corner {
            position: absolute;
            width: 100px;
            height: 100px;
        }
        .top-left {
            top: 0;
            left: 0;
            border-top: 20px solid #e0a080;
            border-left: 20px solid #e0a080;
        }
        .top-right {
            top: 0;
            right: 0;
            border-top: 20px solid #304060;
            border-right: 20px solid #304060;
        }
        .bottom-left {
            bottom: 0;
            left: 0;
            border-bottom: 20px solid #304060;
            border-left: 20px solid #304060;
        }
        .bottom-right {
            bottom: 0;
            right: 0;
            border-bottom: 20px solid #e0a080;
            border-right: 20px solid #e0a080;
        }
        h1 {
            color: #9C490C;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        h2 {
            color: #020202;
            font-size: 24px;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .recipient {
            font-size: 32px;
            font-weight: bold;
            color: #9c4902;
            margin-bottom: 30px;
        }
        p {
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 30px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature {
            text-align: center;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            margin-bottom: 10px;
        }
        .signature-name {
            font-weight: bold;
        }
        .signature-title {
            font-style: italic;
        }
        .medal {
            color: #9C490C;
            font-size: 64px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="corner top-left"></div>
        <div class="corner top-right"></div>
        <div class="corner bottom-left"></div>
        <div class="corner bottom-right"></div>

        <h1 class="text-center">AGS AUTOMOBILLE</h1>
        <h2 class="text-center">CERTIFICAT D'APPRÉCIATION</h2>
        <p class="text-center">Ce certificat est fièrement présenté à</p>
        <p class="recipient text-center">{{ $user->nom_complet }}</p>
        <p class="text-center">pour avoir complété avec succès la formation<br><strong>{{ $formation->nom_formation }}</strong></p>
        <p class="text-center">Ce certificat atteste que l'utilisateur mentionné a acquis les compétences nécessaires et a démontré un engagement exceptionnel dans l'exécution de ses responsabilités au sein de notre entreprise.</p>

        <div class="signatures">
            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-name">Alpha DIALLO</div>
                <div class="signature-title">Président</div>
            </div>
            <div class="text-center">
                <i class="fas fa-medal medal"></i>
            </div>
            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-name">ISSA LAY</div>
                <div class="signature-title">Directeur</div>
            </div>
        </div>

        <p class="text-center mt-4">Date de délivrance : {{ $date }}</p>
    </div>
</body>
</html>
