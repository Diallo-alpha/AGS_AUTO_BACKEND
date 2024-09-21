<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePanierRequest;
use App\Http\Requests\UpdatePanierRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Produit;
use App\Models\Formation;

class CartController extends Controller
{
    public function obtenirPanier()
    {
        $user = Auth::user();
        return response()->json(['panier' => $user->cart ?? []]);
    }

    public function ajouterAuPanier(StorePanierRequest $request)
    {
        $user = Auth::user();
        $panier = $user->cart ?? [];

        $cleItem = $request->item_type . '_' . $request->item_id;

        if (isset($panier[$cleItem])) {
            $panier[$cleItem]['quantite'] += $request->quantity;
        } else {
            $item = $request->item_type === 'produit'
                ? Produit::findOrFail($request->item_id)
                : Formation::findOrFail($request->item_id);

            $panier[$cleItem] = [
                'id' => $item->id,
                'type' => $request->item_type,
                'nom' => $request->item_type === 'produit' ? $item->nom_produit : $item->nom_formation, 
                'prix' => $item->prix,
                'quantite' => $request->quantity
            ];
        }

        $user->cart = $panier;
        $user->save();

        return response()->json(['message' => 'Article ajouté au panier', 'panier' => $panier]);
    }

    public function retirerDuPanier(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'item_type' => 'required|in:produit,formation',
        ]);

        $user = Auth::user();
        $panier = $user->cart ?? [];

        $cleItem = $request->item_type . '_' . $request->item_id;

        if (isset($panier[$cleItem])) {
            unset($panier[$cleItem]);
            $user->cart = $panier;
            $user->save();
        }

        return response()->json(['message' => 'Article retiré du panier', 'panier' => $panier]);
    }

    public function mettreAJourQuantite(UpdatePanierRequest $request)
    {
        $user = Auth::user();
        $panier = $user->cart ?? [];

        $cleItem = $request->item_type . '_' . $request->item_id;

        if (isset($panier[$cleItem])) {
            $panier[$cleItem]['quantite'] = $request->quantity;
            $user->cart = $panier;
            $user->save();
        }

        return response()->json(['message' => 'Quantité mise à jour', 'panier' => $panier]);
    }
}
