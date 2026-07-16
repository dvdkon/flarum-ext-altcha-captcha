<?php

use Flarum\Extend;
use Flarum\User\Event\Saving;
use AltchaCaptcha\Api\ChallengeController;
use AltchaCaptcha\Listener\ValidateAltchaToken;
use AltchaCaptcha\Middleware\ValidateLoginAltcha;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('forum'))
        ->get('/altcha-challenge', 'dvdkon-altcha-captcha.challenge', ChallengeController::class),

    (new Extend\Event())
        ->listen(Saving::class, ValidateAltchaToken::class),

    (new Extend\Middleware('forum'))
        ->add(ValidateLoginAltcha::class),

    (new Extend\Settings())
        ->default('dvdkon-altcha-captcha.protect_registration', true)
        ->default('dvdkon-altcha-captcha.protect_login', false)
        ->serializeToForum('dvdkon-altcha-captcha.protect_registration', 'dvdkon-altcha-captcha.protect_registration', 'boolval')
        ->serializeToForum('dvdkon-altcha-captcha.protect_login', 'dvdkon-altcha-captcha.protect_login', 'boolval'),
];
