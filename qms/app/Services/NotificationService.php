<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NotificationRepository;

/**
 * Service de notifications : centre applicatif + envoi email optionnel.
 * Le canal « push mobile » est exposé côté client via l'API REST et le
 * polling du centre de notifications.
 */
final class NotificationService
{
    public function __construct(private readonly NotificationRepository $repository)
    {
    }

    public function notify(int $userId, string $type, string $title, string $message, ?string $link = null): void
    {
        $this->repository->create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
            'is_read' => 0,
        ]);

        if (config('mail.enabled')) {
            $this->sendEmail($userId, $title, $message);
        }
    }

    /**
     * Notifie une liste d'utilisateurs.
     *
     * @param array<int, int> $userIds
     */
    public function notifyMany(array $userIds, string $type, string $title, string $message, ?string $link = null): void
    {
        foreach (array_unique($userIds) as $userId) {
            if ($userId > 0) {
                $this->notify($userId, $type, $title, $message, $link);
            }
        }
    }

    private function sendEmail(int $userId, string $title, string $message): void
    {
        // Point d'extension : intégration d'un transport SMTP (PHPMailer, Symfony Mailer...).
        // Journalisé ici pour tracer l'intention d'envoi sans dépendance externe.
        error_log(sprintf('[QMS][MAIL] Notification à l\'utilisateur #%d : %s', $userId, $title));
    }
}
