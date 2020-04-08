<?php

namespace App\View\Filter;

use App\Entity\Subject;
use App\Grouping\Grouper;
use App\Repository\SubjectRepositoryInterface;
use App\Sorting\Sorter;
use App\Sorting\SubjectNameStrategy;
use App\Utils\ArrayUtils;

class SubjectFilter {

    private $sorter;
    private $subjectRepository;

    public function __construct(Sorter $sorter, SubjectRepositoryInterface $subjectRepository) {
        $this->sorter = $sorter;
        $this->subjectRepository = $subjectRepository;
    }

    public function handle(?int $subjectId, bool $onlySubjectsWithTeachers = true) {
        if($onlySubjectsWithTeachers === true) {
            $subjects = $this->subjectRepository->findAllWithTeachers();
        } else {
            $subjects = $this->subjectRepository->findAll();
        }

        $this->sorter->sort($subjects, SubjectNameStrategy::class);

        $subjects = ArrayUtils::createArrayWithKeys($subjects, function(Subject $subject) {
            return $subject->getId();
        });

        $subject = $subjectId !== null ?
            $subjects[$subjectId] ?? null : null;

        return new SubjectFilterView($subjects, $subject);
    }
}