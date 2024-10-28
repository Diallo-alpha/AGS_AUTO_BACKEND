<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Formation;
use App\Models\Video;
use App\Models\Ressource;

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
/**
 * Récupère les statistiques des formations, vidéos et ressources
 *
 * @return \Illuminate\Http\JsonResponse
 */
public function getContentStatistics()
{
    try {
        // Statistiques des formations
        $formationStats = [
            'total' => Formation::count(),
            'with_videos' => Formation::has('videos')->count()
        ];

        // Calcul de la moyenne de vidéos par formation
        $averageVideosPerFormation = DB::table('videos')
            ->groupBy('formation_id')
            ->select(DB::raw('formation_id, COUNT(*) as total'))
            ->get()
            ->average('total') ?? 0;

        // Statistiques des vidéos
        $videoStats = [
            'total' => Video::count(),
            'with_resources' => Video::has('ressources')->count()
        ];

        // Calcul de la moyenne de ressources par vidéo
        $averageResourcesPerVideo = DB::table('ressources')
            ->groupBy('video_id')
            ->select(DB::raw('video_id, COUNT(*) as total'))
            ->get()
            ->average('total') ?? 0;

        // Nombre total de ressources
        $totalResources = Ressource::count();

        // Préparation des données pour les graphiques
        $chartData = [
            'content_distribution' => [
                'labels' => ['Formations', 'Vidéos', 'Ressources'],
                'datasets' => [
                    [
                        'data' => [
                            $formationStats['total'],
                            $videoStats['total'],
                            $totalResources
                        ],
                        'backgroundColor' => [
                            '#FF6384', // Rouge pour formations
                            '#36A2EB', // Bleu pour vidéos
                            '#FFCE56'  // Jaune pour ressources
                        ],
                        'label' => 'Distribution du contenu'
                    ]
                ]
            ],
            'formations_details' => [
                'labels' => ['Avec vidéos', 'Sans vidéos'],
                'datasets' => [
                    [
                        'data' => [
                            $formationStats['with_videos'],
                            $formationStats['total'] - $formationStats['with_videos']
                        ],
                        'backgroundColor' => ['#4CAF50', '#f44336'],
                        'label' => 'Formations avec/sans vidéos'
                    ]
                ]
            ],
            'videos_details' => [
                'labels' => ['Avec ressources', 'Sans ressources'],
                'datasets' => [
                    [
                        'data' => [
                            $videoStats['with_resources'],
                            $videoStats['total'] - $videoStats['with_resources']
                        ],
                        'backgroundColor' => ['#2196F3', '#FF9800'],
                        'label' => 'Vidéos avec/sans ressources'
                    ]
                ]
            ]
        ];

        // Statistiques détaillées avec pourcentages
        $detailedStats = [
            'formations' => [
                'total' => $formationStats['total'],
                'with_videos_percentage' => $formationStats['total'] > 0
                    ? round(($formationStats['with_videos'] / $formationStats['total']) * 100, 1)
                    : 0,
                'average_videos_per_formation' => round($averageVideosPerFormation, 1)
            ],
            'videos' => [
                'total' => $videoStats['total'],
                'with_resources_percentage' => $videoStats['total'] > 0
                    ? round(($videoStats['with_resources'] / $videoStats['total']) * 100, 1)
                    : 0,
                'average_resources_per_video' => round($averageResourcesPerVideo, 1)
            ],
            'resources' => [
                'total' => $totalResources,
                'average_per_video' => round($averageResourcesPerVideo, 1)
            ]
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
            'message' => 'Erreur lors de la récupération des statistiques du contenu',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
