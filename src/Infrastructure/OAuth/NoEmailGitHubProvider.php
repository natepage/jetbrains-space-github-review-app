<?php
declare(strict_types=1);

namespace App\Infrastructure\OAuth;

use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Token\AccessToken;
use UnexpectedValueException;

final class NoEmailGitHubProvider extends Github
{
    /**
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    protected function fetchResourceOwnerDetails(AccessToken $token): array
    {
        $url = $this->getResourceOwnerDetailsUrl($token);
        $request = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token);
        $response = $this->getParsedResponse($request);

        if (false === \is_array($response)) {
            throw new UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        return $response;
    }
}