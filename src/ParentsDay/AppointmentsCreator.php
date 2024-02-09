<?php

namespace App\ParentsDay;

use App\Entity\ParentsDay;
use App\Entity\ParentsDayAppointment;
use App\Entity\Teacher;
use App\Repository\ParentsDayAppointmentRepositoryInterface;
use DateTime;
use SchulIT\CommonBundle\Helper\DateHelper;

class AppointmentsCreator {
    public function __construct(private readonly ParentsDayAppointmentRepositoryInterface $appointmentRepository, private readonly DateHelper $dateHelper) {

    }

    public function createAppointments(Teacher $teacher, AppointmentsCreatorParams $params): void {
        $existingAppointments = $this->appointmentRepository->findForTeacher($teacher, $params->parentsDay);

        $start = $this->getDateTime($params->parentsDay->getDate(), $params->from);
        $end = $this->getDateTime($params->parentsDay->getDate(), $params->until);

        $currentStart = clone $start;
        $currentEnd = (clone $currentStart)->modify(sprintf('+%d minutes', $params->duration));

        while($currentEnd <= $end) {
            $newAppointment = (new ParentsDayAppointment())
                ->setParentsDay($params->parentsDay)
                ->setStart($currentStart)
                ->setEnd($currentEnd);

            $newAppointment->addTeacher($teacher);

            $conflictingAppointments = $this->getConflictingAppointments($existingAppointments, $newAppointment);

            if(count($conflictingAppointments) === 0) {
                $this->appointmentRepository->persist($newAppointment);
            } // TODO: conflict handling

            $currentStart = (clone $currentStart)->modify(sprintf('+%d minutes', $params->duration));
            $currentEnd = (clone $currentStart)->modify(sprintf('+%d minutes', $params->duration));
        }
    }

    private function getDateTime(DateTime $dateTime, string $time): DateTime {
        [$hours, $minutes] = explode(':', $time);

        return (clone $dateTime)->setTime($hours, $minutes, 0);
    }

    /**
     * @param ParentsDayAppointment[] $existingAppointments
     * @param ParentsDayAppointment $newAppointment
     * @return ParentsDayAppointment[]
     */
    private function getConflictingAppointments(array $existingAppointments, ParentsDayAppointment $newAppointment): array {
        $conflicts = [ ];

        foreach($existingAppointments as $appointment) {
            if($this->dateHelper->isBetween($appointment->getStartDateTime(), $newAppointment->getStartDateTime(), $newAppointment->getEndDateTime())
                || $this->dateHelper->isBetween($appointment->getEndDateTime(), $newAppointment->getStartDateTime(), $newAppointment->getEndDateTime())) {
                $conflicts[] = $appointment;
            }
        }

        return $conflicts;
    }
}