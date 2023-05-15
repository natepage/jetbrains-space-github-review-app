<?php
declare(strict_types=1);

namespace App\Controller\SpaceWebhook;

use App\Controller\AbstractController;
use App\Space\PublicKeySignatureVerifier as SpacePublicKeySignatureVerifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractSpaceWebhookController extends AbstractController
{
    private readonly SpacePublicKeySignatureVerifier $publicKeySignatureVerifier;

    public function __invoke(Request $request): Response
    {
        return $this->handleApiResponse(function () use ($request): ?Response {
            // Make sure request is coming from Space
            $this->publicKeySignatureVerifier->isSignatureValidForRequest($request);

            return $this->doHandleWebhook($request);
        });
    }

    #[Required]
    public function setSignatureVerifier(SpacePublicKeySignatureVerifier $publicKeySignatureVerifier): void
    {
        $this->publicKeySignatureVerifier = $publicKeySignatureVerifier;
    }

    abstract protected function doHandleWebhook(Request $request): ?Response;
}