<?php

namespace AltchaCaptcha\Listener;

use Flarum\Foundation\ValidationException;
use Flarum\Locale\Translator;
use Flarum\User\Event\Saving;
use AltchaCaptcha\AltchaValidator;

class ValidateAltchaToken
{
    protected $validator;
    protected $translator;

    public function __construct(AltchaValidator $validator, Translator $translator)
    {
        $this->validator = $validator;
        $this->translator = $translator;
    }

    public function handle(Saving $event): void
    {
        // Only validate on new user registration
        if ($event->user->exists) {
            return;
        }

        if (! $this->validator->shouldProtect('registration')) {
            return;
        }

        // Admin creating users shouldn't need CAPTCHA
        if ($event->actor->isAdmin()) {
            return;
        }

        $payload = $event->data['attributes']['altcha'] ?? '';

        if (! $this->validator->verify($payload)) {
            throw new ValidationException([
                'altcha' => $this->translator->trans('dvdkon-altcha-captcha.api.invalid_captcha'),
            ]);
        }
    }
}
