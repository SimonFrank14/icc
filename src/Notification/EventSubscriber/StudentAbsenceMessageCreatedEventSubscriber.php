<?php

namespace App\Notification\EventSubscriber;

use App\Event\StudentAbsenceMessageCreatedEvent;
use App\Notification\NotificationService;
use App\Notification\StudentAbsenceNotification;
use App\StudentAbsence\InvolvedUsersResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StudentAbsenceMessageCreatedEventSubscriber implements EventSubscriberInterface {

    public function __construct(private readonly InvolvedUsersResolver $involvedUsersResolver, private readonly NotificationService $notificationService,
                                private readonly UrlGeneratorInterface $urlGenerator, private readonly TranslatorInterface $translator) {

    }

    public function onMessageCreated(StudentAbsenceMessageCreatedEvent $event): void {
        $recipients = [ ];
        foreach($this->involvedUsersResolver->resolveUsers($event->getAbsence()) as $user) {
            if($event->getMessage()->getCreatedBy()->getId() !== $user->getId()) {
                $recipients[] = $user;
            }
        }

        foreach($recipients as $recipient) {
            $notification = new StudentAbsenceNotification(
                $recipient,
                $this->translator->trans('student_absence.message.title', [], 'email'),
                $this->translator->trans('student_absence.message.content', [], 'email'),
                $this->urlGenerator->generate('show_student_absence', [ 'uuid' => $event->getAbsence()->getUuid()->toString()], UrlGeneratorInterface::ABSOLUTE_URL),
                $this->translator->trans('student_absence.link', [], 'email'),
                $event->getAbsence(),
                sprintf('%s %s', $event->getMessage()->getCreatedBy()->getFirstname(), $event->getMessage()->getCreatedBy()->getLastname())
            );

            $this->notificationService->notify($notification);
        }
    }

    public static function getSubscribedEvents(): array {
        return [
            StudentAbsenceMessageCreatedEvent::class => 'onMessageCreated'
        ];
    }
}