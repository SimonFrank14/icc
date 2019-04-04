<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 */
class Document {

    /**
     * @ORM\GeneratedValue()
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Gedmo\Slug(fields={"name"})
     * @var string
     */
    private $alias;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="DocumentCategory", inversedBy="documents")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Assert\NotNull()
     * @var DocumentCategory
     */
    private $category;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotNull()
     * @Assert\NotBlank()
     * @var string
     */
    private $content;

    /**
     * @ORM\ManyToMany(targetEntity="StudyGroup")
     * @ORM\JoinTable(
     *     name="document_studygroups",
     *     joinColumns={@ORM\JoinColumn(name="page", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="studygroup", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"name" = "ASC"})
     * @var ArrayCollection<StudyGroup>
     */
    private $studyGroups;

    /**
     * @ORM\OneToMany(targetEntity="DocumentAttachment", mappedBy="document")
     * @var ArrayCollection<DocumentAttachment>
     */
    private $attachments;

    /**
     * @ORM\ManyToMany(targetEntity="DocumentVisibility")
     * @ORM\JoinTable(name="document_visibilities",
     *     joinColumns={@ORM\JoinColumn(name="document")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="visibility")}
     * )
     * @var ArrayCollection<MessageVisibility>
     */
    private $visibilities;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     * @Gedmo\Timestampable(on="create")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity="User")
     * @ORM\JoinTable(
     *     name="document_authors",
     *     joinColumns={@ORM\JoinColumn(name="page", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user", onDelete="CASCADE")}
     * )
     * @var ArrayCollection<Teacher>
     */
    private $authors;

    public function __construct() {
        $this->studyGroups = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->visibilities = new ArrayCollection();
        $this->authors = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAlias(): string {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Document
     */
    public function setName(string $name): Document {
        $this->name = $name;
        return $this;
    }

    /**
     * @return DocumentCategory|null
     */
    public function getCategory(): ?DocumentCategory {
        return $this->category;
    }

    /**
     * @param DocumentCategory $category
     * @return Document
     */
    public function setCategory(DocumentCategory $category): Document {
        $this->category = $category;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * @param string $content
     * @return Document
     */
    public function setContent(string $content) {
        $this->content = $content;
        return $this;
    }

    public function addStudyGroup(StudyGroup $studyGroup) {
        $this->studyGroups->add($studyGroup);
    }

    public function removeStudyGroup(StudyGroup $studyGroup) {
        $this->studyGroups->removeElement($studyGroup);
    }

    /**
     * @return ArrayCollection<StudyGroup>
     */
    public function getStudyGroups(): ArrayCollection {
        return $this->studyGroups;
    }

    public function addAttachment(DocumentAttachment $attachment) {
        $this->attachments->add($attachment);
    }

    public function removeAttachment(DocumentAttachment $attachment) {
        $this->attachments->removeElement($attachment);
    }

    /**
     * @return ArrayCollection<DocumentAttachment>
     */
    public function getAttachments() {
        return $this->attachments;
    }

    public function addVisibility(MessageVisibility $visibility) {
        $this->visibilities->add($visibility);
    }

    public function removeVisibility(MessageVisibility $visibility) {
        $this->visibilities->removeElement($visibility);
    }

    /**
     * @return ArrayCollection<MessageVisibility>
     */
    public function getVisibilities() {
        return $this->visibilities;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime {
        return $this->updatedAt;
    }

    public function addAuthor(Teacher $teacher) {
        $this->authors->add($teacher);
    }

    public function removeAuthor(Teacher $teacher) {
        $this->authors->removeElement($teacher);
    }

    /**
     * @return ArrayCollection<Teacher>
     */
    public function getAuthors() {
        return $this->authors;
    }

}