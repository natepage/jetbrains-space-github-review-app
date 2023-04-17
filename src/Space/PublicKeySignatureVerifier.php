<?php
declare(strict_types=1);

namespace App\Space;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

final class PublicKeySignatureVerifier
{
    private const URL = 'https://eonx.jetbrains.space/api/http/applications/clientId:%s/public-keys';

    public function __construct(
        private readonly AccessTokenProvider $accessTokenProvider,
        private readonly CacheInterface $flysystemCache,
        private readonly HttpClientInterface $httpClient,
        private readonly string $spaceClientId
    ){
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isSignatureValidForRequest(Request $request): void
    {
        $requestTimestamp = $request->headers->get('X-Space-Timestamp');
        $requestSignature = $request->headers->get('X-Space-Public-Key-Signature');

        if ($requestTimestamp === null || $requestSignature === null) {
            throw new \RuntimeException('Invalid request headers');
        }

        $string = \sprintf("%s:%s", $requestTimestamp, $request->getContent());

        foreach ($this->getPublicKeys() as $key) {
            $verify = \openssl_verify(
                $string,
                JWT::urlsafeB64Decode($requestSignature),
                $key->getKeyMaterial(),
                'SHA512'
            );

            if ($verify === 1) {
                return;
            }
        }

        throw new \RuntimeException('Invalid signature');
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getPublicKeys(): array
    {
        return JWK::parseKeySet($this->flysystemCache->get('space_public_keys', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            $url = \sprintf(self::URL, $this->spaceClientId);
            $response = $this->httpClient->request('GET', $url, [
                'auth_bearer' => $this->accessTokenProvider->getAccessToken(),
            ])->getContent();

            $content = u($response)
                ->trimStart('"')
                ->trimEnd('"')
                ->replace('\\', '')
                ->toString();

            return \json_decode($content, true);
        }, \INF));
    }
}