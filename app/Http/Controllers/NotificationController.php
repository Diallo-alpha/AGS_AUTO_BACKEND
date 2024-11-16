<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Affiche toutes les notifications.
     */
    public function getAllNotifications()
    {
        try {
            // Vérifier si l'utilisateur est connecté et a le rôle admin
            if (!auth()->check() || !auth()->user()->hasRole('admin')) {
                return response()->json(['message' => 'Accès refusé'], 403);
            }

            // Récupérer toutes les notifications de l'utilisateur
            $notifications = auth()->user()
                ->notifications()
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'notifications' => $notifications
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération des notifications : ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la récupération des notifications',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Affiche une notification spécifique.
     */
    public function getNotification($id)
    {
        try {
            // Vérifier si l'utilisateur est connecté et a le rôle admin
            if (!auth()->check() || !auth()->user()->hasRole('admin')) {
                return response()->json(['message' => 'Accès refusé'], 403);
            }

            // Récupérer la notification spécifique
            $notification = auth()->user()
                ->notifications()
                ->where('id', $id)
                ->first();

            if (!$notification) {
                return response()->json(['message' => 'Notification non trouvée'], 404);
            }

            // Marquer la notification comme lue
            $notification->markAsRead();

            return response()->json([
                'notification' => $notification
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la récupération de la notification : ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la récupération de la notification',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime une notification.
     */
    public function deleteNotification($id)
    {
        try {
            // Vérifier si l'utilisateur est connecté et a le rôle admin
            if (!auth()->check() || !auth()->user()->hasRole('admin')) {
                return response()->json(['message' => 'Accès refusé'], 403);
            }

            // Récupérer la notification
            $notification = auth()->user()
                ->notifications()
                ->where('id', $id)
                ->first();

            if (!$notification) {
                return response()->json(['message' => 'Notification non trouvée'], 404);
            }

            // Marquer la notification comme lue
            $notification->markAsRead();

            // Supprimer la notification
            $notification->delete();

            return response()->json([
                'message' => 'Notification supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de la notification : ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la suppression de la notification',
                'erreur' => $e->getMessage()
            ], 500);
        }
    }
}
