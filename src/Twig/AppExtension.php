<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private NotificationRepository $notifRepo,
        private Security $security,
    ) {}

    public function getGlobals(): array
    {
        $user = $this->security->getUser();
        $unreadCount = 0;
        if ($user) {
            $unreadCount = $this->notifRepo->count(['user' => $user, 'is_read' => false]);
        }
        return [
            'notif_unread_count' => $unreadCount,
        ];
    }
}
