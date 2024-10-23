<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Récupère les statistiques des utilisateurs pour les rôles étudiant et client
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserStatistics()
    {
        try {
            // Récupération des stats uniquement pour les rôles étudiant et client
            $roleStats = User::select('role', DB::raw('count(*) as count'))
                ->whereIn('role', ['etudiant', 'client'])
                ->groupBy('role')
                ->orderBy('role')
                ->get();

            // Calcul du nombre total d'utilisateurs (seulement étudiants et clients)
            $totalUsers = $roleStats->sum('count');

            // Préparation des couleurs spécifiques pour ces deux rôles
            $colors = [
                'etudiant' => '#4CAF50', // Vert pour les étudiants
                'client' => '#2196F3'    // Bleu pour les clients
            ];

            // Préparation des données pour ng2-charts
            $chartData = [
                'labels' => $roleStats->pluck('role')->map(function($role) {
                    return ucfirst($role); // Première lettre en majuscule
                })->toArray(),
                'datasets' => [
                    [
                        'data' => $roleStats->pluck('count')->toArray(),
                        'backgroundColor' => $roleStats->map(function($stat) use ($colors) {
                            return $colors[$stat->role];
                        })->toArray(),
                        'label' => 'Répartition Étudiants/Clients'
                    ]
                ]
            ];

            // Calcul des pourcentages
            $statsWithPercentage = $roleStats->map(function($stat) use ($totalUsers) {
                return [
                    'role' => ucfirst($stat->role), // Première lettre en majuscule
                    'count' => $stat->count,
                    'percentage' => $totalUsers > 0
                        ? round(($stat->count / $totalUsers) * 100, 1)
                        : 0
                ];
            });

            // Statistiques détaillées
            $detailedStats = [
                'totalUsers' => $totalUsers,
                'etudiants' => $roleStats->where('role', 'etudiant')->first()->count ?? 0,
                'clients' => $roleStats->where('role', 'client')->first()->count ?? 0,
                'distribution' => $statsWithPercentage
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $detailedStats,
                    'chartData' => $chartData,
                    'lastUpdate' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
