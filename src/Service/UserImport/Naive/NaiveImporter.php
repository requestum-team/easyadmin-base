<?php


namespace App\Service\UserImport\Naive;


use App\Entity\User;
use App\Service\UserImport\ImporterInterface;
use App\Service\UserImport\ImportException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Most quick and simple implementation, that will work only for small amount of records.
 * If import files will contain thousands of record it's better process import as a background task
 *
 * Class NaiveImporter
 */
class NaiveImporter implements ImporterInterface
{
    /** @var MailerInterface  */
    private $mailer;

    /** @var UserPasswordHasherInterface */
    private $passwordHasher;

    /** @var EntityManagerInterface */
    private $em;

    /** @var ValidatorInterface */
    private $validator;

    /** @var SerializerInterface */
    private $serializer;

    /** @var string */
    private $emailSender;

    /** @var string */
    private $siteDomain;

    public function __construct(
        MailerInterface $mailer,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        $emailSender,
        $siteDomain
    ) {
        $this->mailer = $mailer;
        $this->passwordHasher = $passwordHasher;
        $this->em = $em;
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->emailSender = $emailSender;
        $this->siteDomain = $siteDomain;
    }

    public function import(File $file) {
        $content = $file->getContent();

        try {
            $DTOs = $this->serializer->deserialize($content, UserDTO::class.'[]', 'csv');
        } catch (ExceptionInterface $exception) {
            throw new ImportException("Can't parse import data.");
        }

        if (count($DTOs) === 0) {
            throw new ImportException("Can't parse import data.");
        }

        $index = 0;
        $imported = [];

        try {
            foreach ($DTOs as $DTO) {
                $userInfo = $this->importUser($DTO, $index++);
                $imported[] = $userInfo;
            }

            $this->em->flush();

            foreach ($imported as [$user, $password]) {
                $this->sendEmail($user, $password);
            }
        } catch (UniqueConstraintViolationException $exception) {
            throw new ImportException("Duplicate users");
        }

        return $index;
    }

    protected function importUser(UserDTO $DTO, $index) {
        $user = $this->createUser($DTO);

        if (count($this->validator->validate($user))) {
            throw new ImportException(sprintf("Invalid data at record %s", $index));
        }

        $password = md5(random_bytes(10));
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->em->persist($user);

        return [$user, $password];
    }

    protected function createUser(UserDTO $DTO) {
        $user = new User();
        $user->setUsername($DTO->username);
        $user->setEmail($DTO->email);
        $user->setFirstName($DTO->firstname);
        $user->setLastName($DTO->lastname);

        return $user;
    }

    protected function sendEmail(User $user, $password) {
        $email = (new TemplatedEmail())
            ->from($this->emailSender)
            ->to($user->getEmail())
            ->subject('Welcome to Admin-Base!')
            ->htmlTemplate('emails/invitation.html.twig')
            ->context([
                'username' => $user->getEmail(),
                'password' => $password,
                'link' => $this->siteDomain
            ])
        ;

        $this->mailer->send($email);
    }
}
