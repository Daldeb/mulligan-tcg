<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
        private string $fromEmail
    ) {}

    public function sendVerificationEmail(string $userEmail, string $pseudo, string $token): void
    {
        $verificationUrl = $this->urlGenerator->generate('app_verify_email', [
            'token' => $token
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($userEmail)
            ->subject('Confirmez votre inscription - Mulligan TCG')
            ->html($this->twig->render('emails/verification.html.twig', [
                'pseudo' => $pseudo,
                'verificationUrl' => $verificationUrl
            ]));

        $this->mailer->send($email);
    }

        public function sendPasswordResetEmail(string $userEmail, string $pseudo, string $token): void
    {
        $resetUrl = 'https://mulligan-tcg.fr/password-reset?token=' . $token;

        $email = (new Email())
            ->from($this->fromEmail)
            ->to($userEmail)
            ->subject('Réinitialisation de votre mot de passe - Mulligan TCG')
            ->html($this->twig->render('emails/password_reset.html.twig', [
                'pseudo' => $pseudo,
                'resetUrl' => $resetUrl
            ]));

        $this->mailer->send($email);
    }
}