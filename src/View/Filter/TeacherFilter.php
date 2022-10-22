<?php

namespace App\View\Filter;

use App\Entity\Section;
use App\Entity\Teacher;
use App\Entity\User;
use App\Entity\UserType;
use App\Grouping\Grouper;
use App\Repository\TeacherRepositoryInterface;
use App\Sorting\Sorter;
use App\Sorting\TeacherStrategy;
use App\Utils\ArrayUtils;

class TeacherFilter {

    public function __construct(private Sorter $sorter, private TeacherRepositoryInterface $teacherRepository)
    {
    }

    public function handle(?string $uuid, ?Section $section, User $user, bool $setDefaultTeacher): TeacherFilterView {
        $isStudentOrParent = $user->getUserType()->equals(UserType::Student()) || $user->getUserType()->equals(UserType::Parent());

        $teachers = [ ];

        if($isStudentOrParent !== true && $section !== null) {
            $teachers = $this->teacherRepository->findAllBySection($section);
        }

        $teachers = ArrayUtils::createArrayWithKeys(
            $teachers,
            fn(Teacher $teacher) => (string)$teacher->getUuid()
        );

        $fallbackTeacher = $setDefaultTeacher ? $user->getTeacher() : null;

        $teacher = $uuid !== null ?
            $teachers[$uuid] ?? $fallbackTeacher : $fallbackTeacher;

        $this->sorter->sort($teachers, TeacherStrategy::class);

        return new TeacherFilterView($teachers, $teacher);
    }
}