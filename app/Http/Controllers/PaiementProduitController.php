<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaiement_produitRequest;
use App\Http\Requests\UpdatePaiement_produitRequest;
use App\Models\Paiement_produit;

class PaiementProduitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaiement_produitRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Paiement_produit $paiement_produit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Paiement_produit $paiement_produit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaiement_produitRequest $request, Paiement_produit $paiement_produit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Paiement_produit $paiement_produit)
    {
        //
    }
}
