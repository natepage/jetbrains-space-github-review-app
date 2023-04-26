<?php
declare(strict_types=1);

namespace App\Controller;

use Bugsnag\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractController extends BaseAbstractController
{
    private Client $bugsnag;

    #[Required]
    public function setBugsnagClient(Client $bugsnag): void
    {
        $this->bugsnag = $bugsnag;
    }

    protected function handleApiResponse(callable $func): Response
    {
        try {
            $return = $func();
        } catch (\Throwable $throwable) {
            $this->bugsnag->notifyException($throwable);

            return new JsonResponse(['message' => $throwable->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $return instanceof Response
            ? $return
            : new JsonResponse(['message' => 'OK'], Response::HTTP_OK);
    }
}