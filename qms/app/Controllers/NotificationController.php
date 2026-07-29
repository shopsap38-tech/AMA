<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Repositories\NotificationRepository;

/**
 * Centre de notifications + endpoints API pour le polling temps réel.
 */
final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationRepository $repository,
        private readonly Auth $auth,
    ) {
    }

    public function index(): string
    {
        $userId = (int) $this->auth->id();
        return $this->view('notifications.index', [
            'notifications' => $this->repository->forUser($userId, 50),
            'active'        => 'notifications',
            'title'         => 'Notifications',
        ]);
    }

    /** API : compteur non lues + dernières notifications (polling). */
    public function feed(): never
    {
        $userId = (int) $this->auth->id();
        $this->json([
            'unread' => $this->repository->unreadCount($userId),
            'items'  => $this->repository->forUser($userId, 8),
        ]);
    }

    public function read(Request $request, int $id): never
    {
        $this->repository->markAsRead($id, (int) $this->auth->id());
        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        $this->redirect('notifications');
    }

    public function readAll(Request $request): never
    {
        $this->repository->markAllAsRead((int) $this->auth->id());
        if ($request->wantsJson()) {
            $this->json(['ok' => true]);
        }
        $this->withSuccess('notifications', 'Toutes les notifications ont été marquées comme lues.');
    }
}
