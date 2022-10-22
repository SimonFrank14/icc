<?php

namespace App\StudentAbsence;

use App\Entity\Absence;
use App\Entity\Grade;
use App\Entity\GradeTeacher;
use App\Entity\Student;
use App\Entity\StudentAbsence;
use App\Section\SectionResolverInterface;
use App\Settings\StudentAbsenceSettings;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractNotifier {
    public function __construct(protected string $sender, protected string $appName, protected MailerInterface $mailer, protected SectionResolverInterface $sectionResolver, protected StudentAbsenceSettings $settings, protected TranslatorInterface $translator, protected UrlGeneratorInterface $urlGenerator)
    {
    }

    protected function getTeacherRecipients(StudentAbsence $absence, array $exclude = [ ]): array {
        $to = [ ];
        $section = $this->sectionResolver->getSectionForDate($absence->getFrom()->getDate());
        /** @var Grade|null $grade */
        $grade = $absence->getStudent()->getGrade($section);
        if($grade !== null && $section !== null) {
            /** @var GradeTeacher $teacher */
            foreach ($grade->getTeachers() as $teacher) {
                if($teacher->getSection()->getId() === $section->getId() && !in_array($teacher->getTeacher()->getEmail(), $exclude)) {
                    $to[] = $teacher->getTeacher()->getEmail();
                }
            }
        }

        return $to;
    }
}