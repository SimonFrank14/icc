<?php

namespace App\Doctrine;

use App\Entity\StudentAbsence;
use App\Event\StudentAbsenceCreatedEvent;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StudentAbsencePersistSubscriber implements EventSubscriber {

    public function __construct(private EventDispatcherInterface $dispatcher)
    {
    }

    public function postPersist(LifecycleEventArgs $eventArgs) {
        $entity = $eventArgs->getEntity();

        if($entity instanceof StudentAbsence) {
            $this->dispatcher->dispatch(new StudentAbsenceCreatedEvent($entity));
        }
    }

    /**
     * @inheritDoc
     */
    public function getSubscribedEvents(): array {
        return [
            Events::postPersist
        ];
    }
}