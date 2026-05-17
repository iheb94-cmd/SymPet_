<?php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

final class PaiementController extends AbstractController
{
    #[Route('/commande/{id}/paiement', name: 'app_paiement')]
    public function index(Commande $commande): Response
    {
        // Sécurité : seul le propriétaire peut payer sa commande
        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('paiement/index.html.twig', [
            'commande' => $commande,
            'stripe_public_key' => $this->getParameter('stripe.public_key'),
        ]);
    }

    #[Route('/commande/{id}/paiement/intent', name: 'app_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Commande $commande): JsonResponse
    {
        \Stripe\Stripe::setApiKey($this->getParameter('stripe.secret_key'));

        $intent = \Stripe\PaymentIntent::create([
            'amount' => (int) ($commande->getTotal() * 100), // ✅ cast en int obligatoire
            'currency' => 'eur',
            'metadata' => ['commande_id' => $commande->getId()],
        ]);

        return $this->json(['clientSecret' => $intent->client_secret]);
    }

    #[Route('/commande/{id}/paiement/success', name: 'app_paiement_success')]
    public function success(
        Commande $commande,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        // Sécurité
        if ($commande->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $commande->setStatut('payee');
        $em->flush();

        $this->sendConfirmationEmail($commande, $mailer);

        return $this->render('paiement/success.html.twig', [
            'commande' => $commande,
        ]);
    }

    // ✅ Méthode privée pour l'email
    private function sendConfirmationEmail(Commande $commande, MailerInterface $mailer): void
    {
        $email = (new TemplatedEmail())
            ->from('noreply@sympet.com')
            ->to($commande->getUser()->getEmail())
            ->subject('Confirmation de votre commande #' . $commande->getId())
            ->htmlTemplate('emails/confirmation.html.twig')
            ->context([
                'commande' => $commande,
            ]);

        $mailer->send($email);
    }
}
