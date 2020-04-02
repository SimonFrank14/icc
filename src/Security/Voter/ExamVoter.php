<?php

namespace App\Security\Voter;

use App\Entity\Exam;
use App\Entity\Tuition;
use App\Entity\User;
use App\Entity\UserType;
use App\Settings\ExamSettings;
use SchoolIT\CommonBundle\Helper\DateHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ExamVoter extends Voter {

    public const SHOW = 'show';
    public const INVIGILATORS = 'invigilators';
    public const DETAILS = 'details';

    public const Manage = 'manage-exams';
    public const Add = 'new-exam';
    public const Edit = 'edit';
    public const Remove = 'remove';

    private $dateHelper;
    private $examSettings;
    private $accessDecisionManager;

    public function __construct(DateHelper $dateHelper, ExamSettings $examSettings, AccessDecisionManagerInterface $accessDecisionManager) {
        $this->dateHelper = $dateHelper;
        $this->examSettings = $examSettings;
        $this->accessDecisionManager = $accessDecisionManager;
    }

    /**
     * @inheritDoc
     */
    protected function supports($attribute, $subject) {
        $attributes = [
            static::DETAILS,
            static::INVIGILATORS,
            static::SHOW,
            static::Edit,
            static::Remove
        ];

        return in_array($attribute , [ static::Add, static::Manage ]) || ($subject instanceof Exam && in_array($attribute, $attributes));
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token) {
        switch($attribute) {
            case static::SHOW:
                return $this->canViewExam($subject, $token);

            case static::DETAILS:
                return $this->canViewDetails($subject, $token);

            case static::INVIGILATORS:
                return $this->canViewInvigilators($subject, $token);

            case static::Add:
                return $this->canAdd($token);

            case static::Edit:
                return $this->canEdit($subject, $token);

            case static::Remove:
                return $this->canRemove($subject, $token);

            case static::Manage:
                return $this->canManage($token);
        }

        throw new \LogicException('This code should not be reached.');
    }

    private function getUserType(TokenInterface $token): ?UserType {
        $user = $token->getUser();

        if(!$user instanceof User) {
            return null;
        }

        return $user->getUserType();
    }

    private function isStudentOrParent(TokenInterface $token): bool {
        $userType = $this->getUserType($token);

        if($userType === null) {
            return false;
        }

        return $userType->equals(UserType::Student()) || $userType->equals(UserType::Parent());
    }

    public function canAdd(TokenInterface $token) {
        return $this->accessDecisionManager->decide($token, [ 'ROLE_EXAMS_CREATOR' ]);
    }

    public function canEdit(Exam $exam, TokenInterface $token) {
        if($this->accessDecisionManager->decide($token, ['ROLE_EXAMS_ADMIN']) === true) {
            return true;
        }

        if($exam->getExternalId() !== null) {
            // Non-Admins cannot edit external exams
            return false;
        }

        $user = $token->getUser();

        if(!$user instanceof User) {
            return false;
        }

        $teacher = $user->getTeacher();

        if($teacher === null) {
            return false;
        }

        /** @var Tuition $tuition */
        foreach($exam->getTuitions() as $tuition) {
            if($tuition->getTeacher()->getId() === $teacher->getId()) {
                return true;
            }
        }

        return false;
    }

    public function canManage(TokenInterface $token) {
        if($this->accessDecisionManager->decide($token, ['ROLE_EXAMS_ADMIN']) === true || $this->accessDecisionManager->decide($token, [ 'ROLE_EXAMS_CREATOR'])) {
            return true;
        }

        $user = $token->getUser();

        if(!$user instanceof User) {
            return false;
        }

        $teacher = $user->getTeacher();

        if($teacher === null) {
            return false;
        }

        return true;
    }

    public function canRemove(Exam $exam, TokenInterface $token) {
        if($this->accessDecisionManager->decide($token, ['ROLE_EXAMS_ADMIN']) === true) {
            return true;
        }

        if($exam->getExternalId() !== null) {
            // Non-Admins cannot edit external exams
            return false;
        }

        return $this->accessDecisionManager->decide($token, ['ROLE_EXAMS_CREATOR']) === true;
    }

    public function canViewExam(Exam $exam, TokenInterface $token): bool {
        $userType = $this->getUserType($token);

        if($userType === null) {
            return false;
        }

        if($this->examSettings->isVisibileFor($userType) === false) {
            return false;
        }

        $days = $this->examSettings->getTimeWindowForStudents();
        if($this->isStudentOrParent($token) && $days > 0) {
            $threshold = $this->dateHelper->getToday()
                ->modify(sprintf('+%d days', $days));

            return $exam->getDate() < $threshold;
        }

        return true;
    }

    public function canViewInvigilators(Exam $exam, TokenInterface $token): bool {
        $days = $this->examSettings->getTimeWindowForStudentsToSeeInvigilators();
        if($this->isStudentOrParent($token) && $days > 0) {
            $threshold = $this->dateHelper->getToday()
                ->modify(sprintf('+%d days', $days));

            return $exam->getDate() < $threshold;
        }

        return true;
    }

    private function canViewDetails(Exam $exam, TokenInterface $token): bool {
        return $this->isStudentOrParent($token) === false;
    }
}