<?php

namespace App\Entity;

use Serializable;
use Stringable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
#[UniqueEntity(fields: ['username'])]
class User implements UserInterface, Serializable, Stringable {

    use IdTrait;
    use UuidTrait;

    /**
     * @ORM\Column(type="uuid")
     */
    private ?UuidInterface $idpId = null;

    /**
     * @ORM\Column(type="string", unique=true)
     */
    private ?string $username = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    #[Assert\NotBlank(allowNull: true)]
    private ?string $firstname = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    #[Assert\NotBlank(allowNull: true)]
    private ?string $lastname = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    #[Assert\Email]
    #[Assert\NotBlank(allowNull: true)]
    private ?string $email = null;

    /**
     * @ORM\ManyToOne(targetEntity="Teacher")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private ?Teacher $teacher = null;

    /**
     * @ORM\ManyToMany(targetEntity="Student")
     * @ORM\JoinTable(name="user_students",
     *     joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     * )
     * @var Collection<Student>
     */
    private $students;

    /**
     * @ORM\Column(type="json")
     * @var string[]
     */
    private array $roles = ['ROLE_USER'];

    /**
     * @ORM\Column(type="user_type")
     */
    private ?UserType $userType = null;

    /**
     * @ORM\ManyToMany(targetEntity="Message")
     * @ORM\JoinTable(name="user_dismissed_messages",
     *     joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     * )
     * @var ArrayCollection<Message>
     */
    private $dismissedMessages;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isSubstitutionNotificationsEnabled = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isExamNotificationsEnabled = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isMessageNotificationsEnabled = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isEmailNotificationsEnabled = false;

    /**
     * @ORM\Column(type="json")
     * @var string[]
     */
    private array $data = [ ];

    public function __construct() {
        $this->uuid = Uuid::uuid4();

        $this->students = new ArrayCollection();
        $this->dismissedMessages = new ArrayCollection();
    }

    public function getIdpId(): ?UuidInterface {
        return $this->idpId;
    }

    public function setIdpId(UuidInterface $uuid): User {
        $this->idpId = $uuid;
        return $this;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getFirstname(): ?string {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): User {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): User {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(?string $email): User {
        $this->email = $email;
        return $this;
    }

    public function getTeacher(): ?Teacher {
        return $this->teacher;
    }

    public function setTeacher(?Teacher $teacher): User {
        $this->teacher = $teacher;
        return $this;
    }

    public function getUserType(): UserType {
        return $this->userType;
    }

    public function setUserType(UserType $userType): User {
        $this->userType = $userType;
        return $this;
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles) {
        $this->roles = $roles;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array {
        return $this->roles;
    }

    public function setUsername(string $username): User {
        $this->username = $username;
        return $this;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function addDismissedMessage(Message $message) {
        $this->dismissedMessages->add($message);
    }

    public function removeDismissedMessage(Message $message) {
        $this->dismissedMessages->removeElement($message);
    }

    /**
     * @return Collection<Message>
     */
    public function getDismissedMessages(): Collection {
        return $this->dismissedMessages;
    }

    public function addStudent(Student $student) {
        $this->students->add($student);
    }

    public function removeStudent(Student $student) {
        $this->students->removeElement($student);
    }

    /**
     * @return Collection<Student>
     */
    public function getStudents(): Collection {
        return $this->students;
    }

    public function isSubstitutionNotificationsEnabled(): bool {
        return $this->isSubstitutionNotificationsEnabled;
    }

    public function setIsSubstitutionNotificationsEnabled(bool $isSubstitutionNotificationsEnabled): User {
        $this->isSubstitutionNotificationsEnabled = $isSubstitutionNotificationsEnabled;
        return $this;
    }

    public function isExamNotificationsEnabled(): bool {
        return $this->isExamNotificationsEnabled;
    }

    public function setIsExamNotificationsEnabled(bool $isExamNotificationsEnabled): User {
        $this->isExamNotificationsEnabled = $isExamNotificationsEnabled;
        return $this;
    }

    public function isMessageNotificationsEnabled(): bool {
        return $this->isMessageNotificationsEnabled;
    }

    public function setIsMessageNotificationsEnabled(bool $isMessageNotificationsEnabled): User {
        $this->isMessageNotificationsEnabled = $isMessageNotificationsEnabled;
        return $this;
    }

    public function isEmailNotificationsEnabled(): bool {
        return $this->isEmailNotificationsEnabled;
    }

    public function setIsEmailNotificationsEnabled(bool $isEmailNotificationsEnabled): User {
        $this->isEmailNotificationsEnabled = $isEmailNotificationsEnabled;
        return $this;
    }

    public function getData(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }

    public function setData(string $key, $data): void {
        $this->data[$key] = $data;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): ?string {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): ?string {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials() { }

    public function getUserIdentifier(): string {
        return $this->getUsername();
    }

    /**
     * @inheritDoc
     */
    public function serialize() {
        return serialize([
            $this->getId(),
            $this->getUsername()
        ]);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized) {
        [$this->id, $this->username] = unserialize($serialized);
    }

    public function __toString(): string {
        return sprintf('%s, %s (%s)', $this->getLastname(), $this->getFirstname(), $this->getUsername());
    }
}