<?php

namespace AltchaCaptcha\Api;

use Flarum\User\Exception\PermissionDeniedException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use AltchaCaptcha\AltchaValidator;

class ChallengeController implements RequestHandlerInterface
{
    protected $validator;

    public function __construct(AltchaValidator $validator)
    {
        $this->validator = $validator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! $this->validator->isEnabled()) {
            throw new PermissionDeniedException('Altcha CAPTCHA is not configured.');
        }

        return new JsonResponse($this->validator->createChallenge()->toArray());
    }
}
