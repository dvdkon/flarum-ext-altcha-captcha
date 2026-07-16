<?php

namespace AltchaCaptcha\Middleware;

use Flarum\Foundation\ValidationException;
use Flarum\Locale\Translator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use AltchaCaptcha\AltchaValidator;

class ValidateLoginAltcha implements MiddlewareInterface
{
    protected $validator;
    protected $translator;

    public function __construct(AltchaValidator $validator, Translator $translator)
    {
        $this->validator = $validator;
        $this->translator = $translator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Only intercept POST /login
        if ($method === 'POST' && preg_match('#/login$#', $path)) {
            if ($this->validator->shouldProtect('login')) {
                $body = $request->getParsedBody();
                $payload = $body['altcha'] ?? '';

                if (! $this->validator->verify($payload)) {
                    throw new ValidationException([
                        'altcha' => $this->translator->trans('dvdkon-altcha-captcha.api.invalid_captcha'),
                    ]);
                }
            }
        }

        return $handler->handle($request);
    }
}
