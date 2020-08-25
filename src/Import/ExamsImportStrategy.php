<?php

namespace App\Import;

use App\Entity\Exam;
use App\Entity\ExamSupervision;
use App\Entity\Student;
use App\Entity\Tuition;
use App\Event\ExamImportEvent;
use App\Event\SubstitutionImportEvent;
use App\Repository\ExamRepositoryInterface;
use App\Repository\StudentRepositoryInterface;
use App\Repository\TeacherRepositoryInterface;
use App\Repository\TransactionalRepositoryInterface;
use App\Repository\TuitionRepositoryInterface;
use App\Request\Data\ExamData;
use App\Request\Data\ExamsData;
use App\Utils\CollectionUtils;
use App\Utils\ArrayUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ExamsImportStrategy implements ImportStrategyInterface, PostActionStrategyInterface {

    private $examRepository;
    private $tuitionRepository;
    private $studentRepository;
    private $teacherRepository;
    private $dispatcher;

    public function __construct(ExamRepositoryInterface $examRepository, TuitionRepositoryInterface $tuitionRepository,
                                StudentRepositoryInterface $studentRepository, TeacherRepositoryInterface $teacherRepository, EventDispatcherInterface $eventDispatcher) {
        $this->examRepository = $examRepository;
        $this->tuitionRepository = $tuitionRepository;
        $this->studentRepository = $studentRepository;
        $this->teacherRepository = $teacherRepository;
        $this->dispatcher = $eventDispatcher;
    }

    /**
     * @param ExamsData $requestData
     * @return Exam[]
     */
    public function getExistingEntities($requestData): array {
        return ArrayUtils::createArrayWithKeys(
            $this->examRepository->findAllExternal(),
            function(Exam $exam) {
                return $exam->getExternalId();
            }
        );
    }

    /**
     * @param ExamData $data
     * @param ExamsData $requestData
     * @return Exam
     */
    public function createNewEntity($data, $requestData) {
        $exam = (new Exam())
            ->setExternalId($data->getId());
        $this->updateEntity($exam, $data, $requestData);

        return $exam;
    }

    /**
     * @param ExamData $object
     * @param Exam[] $existingEntities
     * @return Exam|null
     */
    public function getExistingEntity($object, array $existingEntities) {
        return $existingEntities[$object->getId()] ?? null;
    }

    /**
     * @param Exam $entity
     * @return int
     */
    public function getEntityId($entity): int {
        return $entity->getId();
    }

    /**
     * @param Exam $entity
     * @param ExamsData $requestData
     * @param ExamData $data
     */
    public function updateEntity($entity, $data, $requestData): void {
        $entity->setDate($data->getDate());
        $entity->setDescription($data->getDescription());
        $entity->setLessonStart($data->getLessonStart());
        $entity->setLessonEnd($data->getLessonEnd());
        $entity->setRooms($data->getRooms());

        $supervisions = $data->getSupervisions();

        for($lesson = $data->getLessonStart(), $idx = 0; $lesson <= $data->getLessonEnd(); $lesson++, $idx++) {
            $supervision = $entity->getSupervisions()->filter(function(ExamSupervision $supervision) use ($lesson) {
                return $supervision->getLesson() === $lesson;
            })->first();

            if($supervision === false) {
                $supervision = (new ExamSupervision())
                    ->setExam($entity)
                    ->setLesson($lesson);
                $entity->addSupervision($supervision);
            }

            if(!isset($supervisions[$idx])) {
                $entity->removeSupervision($supervision);
                continue;
            }

            $teacher = $this->teacherRepository->findOneByExternalId($supervisions[$idx]);

            if($teacher !== null) {
                $supervision->setTeacher($teacher);
            } else {
                $entity->removeSupervision($supervision);
            }
        }

        CollectionUtils::synchronize(
            $entity->getStudents(),
            $this->studentRepository->findAllByExternalId($data->getStudents()),
            function(Student $student) {
                return $student->getId();
            }
        );

        CollectionUtils::synchronize(
            $entity->getTuitions(),
            $this->tuitionRepository->findAllByExternalId($data->getTuitions()),
            function(Tuition $tuition) {
                return $tuition->getId();
            }
        );
    }

    /**
     * @param Exam $entity
     */
    public function persist($entity): void {
        $this->examRepository->persist($entity);
    }

    /**
     * @param Exam $entity
     */
    public function remove($entity): void {
        $this->examRepository->remove($entity);
    }

    /**
     * @inheritDoc
     */
    public function getRepository(): TransactionalRepositoryInterface {
        return $this->examRepository;
    }

    /**
     * @param ExamsData $data
     * @return ExamData[]
     */
    public function getData($data): array {
        return $data->getExams();
    }

    /**
     * @inheritDoc
     */
    public function getEntityClassName(): string {
        return Exam::class;
    }

    public function onFinished(ImportResult $result) {
        $this->dispatcher->dispatch(new ExamImportEvent($result->getAdded(), $result->getUpdated(), $result->getRemoved()));
    }
}