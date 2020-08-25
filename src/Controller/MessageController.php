<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Entity\MessageConfirmation;
use App\Entity\MessageFile;
use App\Entity\MessageFileUpload;
use App\Entity\MessageScope;
use App\Entity\StudyGroupMembership;
use App\Entity\User;
use App\Entity\UserType;
use App\Filesystem\FileNotFoundException;
use App\Filesystem\MessageFilesystem;
use App\Form\MessageUploadType;
use App\Grouping\Grouper;
use App\Grouping\StudentGradeStrategy;
use App\Grouping\StudentStudyGroupStrategy;
use App\Grouping\UserUserTypeStrategy;
use App\Message\MessageConfirmationViewHelper;
use App\Repository\MessageFileUploadRepositoryInterface;
use App\Repository\MessageRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Security\Voter\MessageVoter;
use App\Sorting\MessageStrategy;
use App\Sorting\Sorter;
use App\Sorting\StudentGradeGroupStrategy;
use App\Sorting\StudentStrategy;
use App\Sorting\StudentStudyGroupGroupStrategy;
use App\Sorting\TeacherStrategy;
use App\Sorting\UserLastnameFirstnameStrategy;
use App\Sorting\UserUserTypeGroupStrategy;
use App\View\Filter\StudentFilter;
use App\View\Filter\UserTypeFilter;
use Doctrine\ORM\EntityManagerInterface;
use SchulIT\CommonBundle\Form\ConfirmType;
use SchulIT\CommonBundle\Helper\DateHelper;
use SchulIT\CommonBundle\Utils\RefererHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/messages")
 */
class MessageController extends AbstractController {

    private $sorter;
    private $dateHelper;

    public function __construct(Sorter $sorter, DateHelper $dateHelper, RefererHelper $refererHelper) {
        parent::__construct($refererHelper);
        
        $this->sorter = $sorter;
        $this->dateHelper = $dateHelper;
    }

    protected function getMessageScope(): MessageScope {
        return MessageScope::Messages();
    }

    /**
     * @Route("", name="messages")
     */
    public function index(MessageRepositoryInterface $messageRepository, StudentFilter $studentFilter, UserTypeFilter $userTypeFilter, Request $request) {
        /** @var User $user */
        $user = $this->getUser();

        $archive = $request->query->get('archive', false) === '✓';
        $studentFilterView = $studentFilter->handle($request->query->get('student', null), $user);
        $userTypeFilterView = $userTypeFilter->handle($request->query->get('user_type', null), $user);

        $studyGroups = [ ];
        if($userTypeFilterView->getCurrentType()->equals(UserType::Student()) || $userTypeFilterView->getCurrentType()->equals(UserType::Parent())) {
            if($studentFilterView->getCurrentStudent() !== null) {
                $studyGroups = $studentFilterView->getCurrentStudent()->getStudyGroupMemberships()->map(function(StudyGroupMembership $membership) {
                    return $membership->getStudyGroup();
                })->toArray();
            }
        }

        $messages = $messageRepository->findBy(
            MessageScope::Messages(),
            $userTypeFilterView->getCurrentType(),
            $this->dateHelper->getToday(),
            $studyGroups,
            $archive
        );

        $this->sorter->sort($messages, MessageStrategy::class);

        return $this->render('messages/index.html.twig', [
            'studentFilter' => $studentFilterView,
            'userTypeFilter' => $userTypeFilterView,
            'messages' => $messages,
            'archive' => $archive
        ]);
    }

    /**
     * @Route("/{uuid}", name="show_message")
     */
    public function show(Message $message, MessageRepositoryInterface $messageRepository, MessageFileUploadRepositoryInterface $fileUploadRepository, MessageFilesystem $messageFilesystem, Request $request) {
        // Requery message for better performance
        $message = $messageRepository->findOneById($message->getId());

        $this->denyAccessUnlessGranted(MessageVoter::View, $message);

        /** @var User $user */
        $user = $this->getUser();

        /** @var MessageFileUpload[] $uploads */
        $uploads = [ ];

        /** @var MessageFile $file */
        foreach($message->getFiles() as $file) {
            $fileUpload = $fileUploadRepository->findOneByFileAndUser($file, $user);

            if($fileUpload === null) {
                $fileUpload = (new MessageFileUpload())
                    ->setUser($user)
                    ->setMessageFile($file);
            }

            $uploads[] = $fileUpload;
        }

        $form = $this->createForm(MessageUploadType::class, [
            'uploads' => $uploads
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            foreach($uploads as $upload) {
                if($upload->getFile() !== null) {
                    $fileUploadRepository->persist($upload);
                }
            }

            return $this->redirectToRoute('show_message', [
                'uuid' => $message->getUuid()
            ]);
        }

        $missing = array_filter($uploads, function(MessageFileUpload $upload) {
            return $upload->isUploaded() === false;
        });

        return $this->render('messages/show.html.twig', [
            'message' => $message,
            'downloads' => $messageFilesystem->getUserDownloads($message, $user),
            'uploads' => $uploads,
            'missing' => $missing,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/attachments/{uuid}", name="download_message_attachment")
     */
    public function downloadAttachment(MessageAttachment $attachment, MessageFilesystem $messageFilesystem) {
        $this->denyAccessUnlessGranted(MessageVoter::View, $attachment->getMessage());

        try {
            return $messageFilesystem->getMessageAttachmentDownloadResponse($attachment);
        } catch (FileNotFoundException $exception) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @Route("/{uuid}/downloads/{filename}", name="download_user_file")
     */
    public function downloadUserFile(Message $message, string $filename, MessageFilesystem $messageFilesystem) {
        /** @var User $user */
        $user = $this->getUser();
        $this->denyAccessUnlessGranted(MessageVoter::View, $message);
        $this->denyAccessUnlessGranted(MessageVoter::Download, $message);

        try {
            return $messageFilesystem->getMessageUserFileDownloadResponse($message, $user, $filename);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @Route("/uploads/{uuid}/download", name="download_uploaded_user_file")
     */
    public function downloadUploadedUserFile(MessageFile $file, MessageFileUploadRepositoryInterface $fileUploadRepository, MessageFilesystem $messageFilesystem) {
        /** @var User $user */
        $user = $this->getUser();
        $this->denyAccessUnlessGranted(MessageVoter::View, $file->getMessage());
        $this->denyAccessUnlessGranted(MessageVoter::Upload, $file->getMessage());

        $fileUpload = $fileUploadRepository->findOneByFileAndUser($file, $user);

        if($fileUpload === null) {
            throw new NotFoundHttpException();
        }

        try {
            return $messageFilesystem->getMessageUploadedUserFileDownloadResponse($fileUpload, $user);
        } catch (FileNotFoundException $e) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @Route("/uploads/{uuid}/remove", name="remove_uploaded_user_file")
     */
    public function removeUploadedUserFile(MessageFile $file, MessageFileUploadRepositoryInterface $fileUploadRepository, MessageFilesystem $filesystem, Request $request) {
        /** @var User $user */
        $user = $this->getUser();
        $this->denyAccessUnlessGranted(MessageVoter::View, $file->getMessage());
        $this->denyAccessUnlessGranted(MessageVoter::Upload, $file->getMessage());

        $fileUpload = $fileUploadRepository->findOneByFileAndUser($file, $user);

        if($fileUpload === null) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(ConfirmType::class, null, [
            'message' => 'messages.uploads.remove.confirm',
            'message_parameters' => [
                '%filename%' => $fileUpload->getFilename(),
                '%label%' => $file->getLabel()
            ]
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            try {
                $fileUploadRepository->remove($fileUpload);
            } catch (FileNotFoundException $e) {
                throw new NotFoundHttpException();
            }

            $this->addFlash('success', 'messages.uploads.remove.success');
            return $this->redirectToRoute('show_message', [
                'uuid' => $file->getMessage()->getUuid()
            ]);
        }

        return $this->render('messages/remove_uploaded_file.html.twig', [
            'message' => $file->getMessage(),
            'file' => $file,
            'upload' => $fileUpload,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{uuid}/confirm", name="confirm_message")
     */
    public function confirm(Message $message, EntityManagerInterface $entityManager) {
        $this->denyAccessUnlessGranted(MessageVoter::Confirm, $message);

        /** @var User $user */
        $user = $this->getUser();

        $confirmations = $message->getConfirmations()
            ->filter(function(MessageConfirmation $confirmation) use ($user) {
                return $confirmation->getUser()->getId() === $user->getId();
            });

        if($confirmations->count() === 0) {
            $confirmation = (new MessageConfirmation())
                ->setMessage($message)
                ->setUser($user);

            $entityManager->persist($confirmation);
            $entityManager->flush();
        }

        return $this->redirectToRequestReferer('show_message', [ 'uuid' => $message->getUuid() ]);
    }

    /**
     * @Route("/{uuid}/dismiss", name="dismiss_message")
     */
    public function dismiss(Message $message, UserRepositoryInterface $userRepository) {
        /** @var User $user */
        $user = $this->getUser();

        if($user->getDismissedMessages()->contains($message) !== true) {
            $user->addDismissedMessage($message);
            $userRepository->persist($user);
        }

        return $this->redirectToRequestReferer('messages');
    }

    /**
     * @Route("/{uuid}/reenable", name="reenable_message")
     */
    public function reenable(Message $message, UserRepositoryInterface $userRepository) {
        /** @var User $user */
        $user = $this->getUser();

        if($user->getDismissedMessages()->contains($message) === true) {
            $user->removeDismissedMessage($message);
            $userRepository->persist($user);
        }

        return $this->redirectToRequestReferer('messages');
    }

    /**
     * @Route("/{uuid}/confirmations", name="message_confirmations")
     */
    public function confirmations(Message $message, MessageConfirmationViewHelper $confirmationViewHelper, Grouper $grouper) {
        $view = $confirmationViewHelper->createView($message);

        $teachers = $view->getTeachers();
        $this->sorter->sort($teachers, TeacherStrategy::class);

        $students = $view->getStudents();
        $gradeGroups = $grouper->group($students, StudentGradeStrategy::class);
        $this->sorter->sort($gradeGroups, StudentGradeGroupStrategy::class);
        $this->sorter->sortGroupItems($gradeGroups, StudentStrategy::class);

        $userGroups = $grouper->group($view->getUsers(), UserUserTypeStrategy::class);
        $this->sorter->sort($userGroups, UserUserTypeGroupStrategy::class);
        $this->sorter->sortGroupItems($userGroups, UserLastnameFirstnameStrategy::class);

        return $this->render('messages/confirmations.html.twig', [
            'message' => $message,
            'teachers' => $teachers,
            'userGroups' => $userGroups,
            'grades' => $gradeGroups,
            'view' => $view
        ]);
    }
}