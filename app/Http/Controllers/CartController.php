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
    private function getCart()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return $user->cart ?? [];
        } else {
            return session()->get('cart', []);
        }
    }

    private function saveCart($cart)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user->cart = $cart;
            $user->save();
        } else {
            session()->put('cart', $cart);
        }
    }

    public function obtenirPanier()
    {
        return response()->json(['panier' => $this->getCart()]);
    }

    public function ajouterAuPanier(StorePanierRequest $request)
    {
        $panier = $this->getCart();

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

        $this->saveCart($panier);

        return response()->json(['message' => 'Article ajouté au panier', 'panier' => $panier]);
    }

    public function retirerDuPanier(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'item_type' => 'required|in:produit,formation',
        ]);

        $panier = $this->getCart();

        $cleItem = $request->item_type . '_' . $request->item_id;

        if (isset($panier[$cleItem])) {
            unset($panier[$cleItem]);
            $this->saveCart($panier);
        }

        return response()->json(['message' => 'Article retiré du panier', 'panier' => $panier]);
    }

    public function mettreAJourQuantite(UpdatePanierRequest $request)
    {
        $panier = $this->getCart();

        $cleItem = $request->item_type . '_' . $request->item_id;

        if (isset($panier[$cleItem])) {
            $panier[$cleItem]['quantite'] = $request->quantity;
            $this->saveCart($panier);
        }

        return response()->json(['message' => 'Quantité mise à jour', 'panier' => $panier]);
    }
}
