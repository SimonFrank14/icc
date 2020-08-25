<?php

namespace App\Import;

use App\Entity\Appointment;
use App\Entity\AppointmentVisibility;
use App\Entity\StudyGroup;
use App\Entity\Teacher;
use App\Entity\UserTypeEntity;
use App\Repository\AppointmentCategoryRepositoryInterface;
use App\Repository\AppointmentRepositoryInterface;
use App\Repository\AppointmentVisibilityRepositoryInterface;
use App\Repository\StudyGroupRepositoryInterface;
use App\Repository\TeacherRepositoryInterface;
use App\Repository\TransactionalRepositoryInterface;
use App\Repository\UserTypeEntityRepositoryInterface;
use App\Request\Data\AppointmentData;
use App\Request\Data\AppointmentsData;
use App\Utils\ArrayUtils;
use App\Utils\CollectionUtils;

class AppointmentsImportStrategy implements ImportStrategyInterface {

    private $appointmentRepository;
    private $appointmentCategoryRepository;
    private $userTypeEntityRepository;
    private $studentGroupRepository;
    private $teacherRepository;

    private $isInitialized = false;

    /**
     * @var UserTypeEntity[]
     */
    private $visibilities = [];

    public function __construct(AppointmentRepositoryInterface $appointmentRepository, AppointmentCategoryRepositoryInterface $appointmentCategoryRepository,
                                UserTypeEntityRepositoryInterface $userTypeEntityRepository, StudyGroupRepositoryInterface $studentRepository,
                                TeacherRepositoryInterface $teacherRepository) {
        $this->appointmentRepository = $appointmentRepository;
        $this->appointmentCategoryRepository = $appointmentCategoryRepository;
        $this->userTypeEntityRepository = $userTypeEntityRepository;
        $this->studentGroupRepository = $studentRepository;
        $this->teacherRepository = $teacherRepository;
    }

    private function initializeIfNecessary() {
        if($this->isInitialized === true) {
            return;
        }

        $this->visibilities = $this->userTypeEntityRepository->findAll();
        $this->isInitialized = true;
    }

    /**
     * @param AppointmentsData $requestData
     * @return array<string, Appointment>
     */
    public function getExistingEntities($requestData): array {
        return ArrayUtils::createArrayWithKeys(
            $this->appointmentRepository->findAll(),
            function (Appointment $appointment) {
                return $appointment->getExternalId();
            }
        );
    }

    /**
     * @param AppointmentData $data
     * @param AppointmentsData $requestData
     * @return Appointment
     * @throws ImportException
     */
    public function createNewEntity($data, $requestData) {
        $appointment = (new Appointment())
            ->setExternalId($data->getId());
        $this->updateEntity($appointment, $data, $requestData);

        return $appointment;
    }

    /**
     * @param AppointmentData $object
     * @param array<string, Appointment> $existingEntities
     * @return Appointment|null
     */
    public function getExistingEntity($object, array $existingEntities) {
        return $existingEntities[$object->getId()] ?? null;
    }

    /**
     * @param Appointment $entity
     * @return int
     */
    public function getEntityId($entity): int {
        return $entity->getId();
    }

    /**
     * @param Appointment $entity
     * @param AppointmentData $data
     * @param AppointmentsData $requestData
     * @throws ImportException
     */
    public function updateEntity($entity, $data, $requestData): void {
        $this->initializeIfNecessary();

        $entity->setStart($data->getStart());
        $entity->setEnd($data->getEnd());
        $entity->setContent($data->getContent());
        $entity->setAllDay($data->isAllDay());
        $entity->setLocation($data->getLocation());
        $entity->setTitle($data->getSubject());

        $category = $this->appointmentCategoryRepository->findOneByExternalId($data->getCategory());

        if($category === null) {
            throw new ImportException(sprintf('Category "%s" on appointment "%s" (ID: %s) was not found.', $data->getCategory(), $data->getSubject(), $data->getId()));
        }

        $entity->setCategory($category);

        CollectionUtils::synchronize(
            $entity->getStudyGroups(),
            $this->studentGroupRepository->findAllByExternalId($data->getStudyGroups()),
            function (StudyGroup $group) {
                return $group->getId();
            }
        );

        CollectionUtils::synchronize(
            $entity->getOrganizers(),
            $this->teacherRepository->findAllByAcronym($data->getOrganizers()),
            function(Teacher $teacher) {
                return $teacher->getId();
            }
        );

        CollectionUtils::synchronize(
            $entity->getVisibilities(),
            array_filter($this->visibilities, function(UserTypeEntity $visibility) use ($data) {
                return in_array($visibility->getUserType()->getValue(), $data->getVisibilities());
            }),
            function(UserTypeEntity $visibility) {
                return $visibility->getId();
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function persist($entity): void {
        $this->appointmentRepository->persist($entity);
    }

    /**
     * @inheritDoc
     */
    public function remove($entity): void {
        $this->appointmentRepository->remove($entity);
    }

    /**
     * @inheritDoc
     */
    public function getRepository(): TransactionalRepositoryInterface {
        return $this->appointmentRepository;
    }

    /**
     * @param AppointmentsData $data
     * @return AppointmentData[]
     */
    public function getData($data): array {
        return $data->getAppointments();
    }

    /**
     * @inheritDoc
     */
    public function getEntityClassName(): string {
        return Appointment::class;
    }
}