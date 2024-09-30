<div class="bg-gray-700  shadow-md text-white rounded-lg p-6">
    {{-- <h1 class="text-3xl font-bold mb-4">Détails du Ticket</h1>
    <p class="mb-2"><strong>Type :</strong> {{ $categorie->nom }}</p>
    <p class="mb-2"><strong>Prix :</strong> {{ $categorie->prix }} FCFA</p>
    <p class="mb-2"><strong>Durée :</strong> {{ $categorie->duration }} jours</p>
    <p class="mb-2"><strong>Limite</strong> --}}

        {{-- <form action="{{ route('payment.initiate') }}" method="POST">
            @csrf
            <input type="hidden" name="categorie_id" value="{{ $categorie->id }}">
            <input type="hidden" name="item_name" value="{{ $categorie->nom }}">
            <input type="hidden" name="item_price" value="{{ $categorie->prix }}">
            <input type="hidden" name="currency" value="XOF"> <!-- Vous pouvez ajuster la devise si nécessaire -->


            <button type="submit" class="bg-blue-400 text-white px-4 py-2 rounded hover:bg-blue-500 mt-4">
                Payer maintenant
            </button>
        </form> --}}
       </p>
       <form action="/api/payment/initiate" method="POST">
        @csrf
        {{-- <input type="hidden" name="categorie_id" value="{{ $categorie->id }}"> --}}
        <input type="hidden" name="item_name" value="test">
        <input type="hidden" name="item_price" value="100">
        <input type="hidden" name="currency" value="XOF"> <!-- Vous pouvez ajuster la devise si nécessaire -->


        <button type="submit" class="bg-blue-400 text-white px-4 py-2 rounded hover:bg-blue-500 mt-4">
            Payer maintenant
        </button>
    </form>


    </div>
