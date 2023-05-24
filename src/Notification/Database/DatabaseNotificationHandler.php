<?php

namespace App\Notification\Database;

use App\Entity\Notification as NotificationEntity;
use App\Notification\Notification;
use App\Notification\NotificationHandlerInterface;
use App\Repository\NotificationRepositoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Stores notifications in the database, so they can be shown in the web GUI.
 */
class DatabaseNotificationHandler implements NotificationHandlerInterface {

    public function __construct(private readonly NotificationRepositoryInterface $notificationRepository, private readonly UrlGeneratorInterface $urlGenerator) {

    }

    public function canHandle(Notification $notification): bool {
        return true; // accept all notifications
    }

    public function handle(Notification $notification): void {
        $entity = (new NotificationEntity())
            ->setRecipient($notification->getRecipient())
            ->setSubject($notification->getSubject())
            ->setContent($notification->getContent())
            ->setLink($notification->getLink())
            ->setLinkText($notification->getLinkText())
            ->setCreatedAt($notification->getCreatedAt());

        // Replace link
        $notification->setLink($this->urlGenerator->generate('notification_redirect', [ 'uuid' => $entity->getUuid()->toString()], UrlGeneratorInterface::ABSOLUTE_URL));

        $this->notificationRepository->persist($entity);
    }

    public function getName(): string {
        return 'database';
    }
}