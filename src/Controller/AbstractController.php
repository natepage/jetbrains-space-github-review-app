<?php
declare(strict_types=1);

namespace App\Controller;

use App\Github\Exceptions\CannotApproveOwnPrException;
use App\Github\Exceptions\NonBlockingValidationException;
use Bugsnag\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractController extends BaseAbstractController
{
    private const SHOULD_NOT_REPORT = [
        CannotApproveOwnPrException::class,
        NonBlockingValidationException::class,
    ];

    private Client $bugsnag;

    #[Required]
    public function setBugsnagClient(Client $bugsnag): void
    {
        $this->bugsnag = $bugsnag;
    }

    protected function handleApiResponse(callable $func): Response
    {
        $return = null;

        try {
            $return = $func();
        } catch (\Throwable $throwable) {
            if ($this->shouldReport($throwable)) {
                $this->bugsnag->notifyException($throwable);

                $return = new JsonResponse(['message' => $throwable->getMessage()], Response::HTTP_BAD_REQUEST);
            }
        }

        return $return instanceof Response
            ? $return
            : new JsonResponse(['message' => 'OK'], Response::HTTP_OK);
    }

    private function shouldReport(\Throwable $throwable): bool
    {
        foreach (self::SHOULD_NOT_REPORT as $shouldNotReport) {
            if (\is_a($throwable, $shouldNotReport)) {
                return false;
            }
        }

        return true;
    }
}