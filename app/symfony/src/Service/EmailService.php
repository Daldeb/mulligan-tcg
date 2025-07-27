<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
        private string $fromEmail = 'noreply@mulligantcg.com'
    ) {}

    public function sendVerificationEmail(User $user): void
    {
        $verificationUrl = $this->urlGenerator->generate(
            'api_verify_email',
            ['token' => $user->getVerificationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Vérifiez votre compte MULLIGAN TCG')
            ->html($this->twig->render('emails/verification.html.twig', [
                'user' => $user,
                'verificationUrl' => $verificationUrl
            ]));

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(User $user): void
    {
        // URL vers votre frontend avec le token
        $resetUrl = sprintf(
            'http://51.178.27.41:5174/reset-password/%s',
            $user->getResetPasswordToken()
        );

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe - MULLIGAN TCG')
            ->html($this->twig->render('emails/password_reset.html.twig', [
                'user' => $user,
                'resetUrl' => $resetUrl
            ]));

        $this->mailer->send($email);
    }

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Bienvenue sur MULLIGAN TCG !')
            ->html($this->twig->render('emails/welcome.html.twig', [
                'user' => $user
            ]));

        $this->mailer->send($email);
    }
}