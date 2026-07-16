<?php

namespace AltchaCaptcha;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\ChallengeParameters;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\Solution;
use AltchaOrg\Altcha\VerifySolutionOptions;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\Log\LoggerInterface;

class AltchaValidator
{
    protected $settings;
    protected $logger;
    protected $cache;

    public function __construct(SettingsRepositoryInterface $settings, LoggerInterface $logger, CacheRepository $cache)
    {
        $this->settings = $settings;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function isEnabled(): bool
    {
        return ! empty($this->getSecret());
    }

    public function createChallenge(): Challenge
    {
        $altcha = $this->altcha();

        $expiry = (int) $this->settings->get('dvdkon-altcha-captcha.expiry', 300);
        $expiry = $expiry > 0 ? $expiry : 300;

        return $altcha->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2(),
            cost: (int) $this->settings->get('dvdkon-altcha-captcha.cost', 20000),
            expiresAt: time() + $expiry,
        ));
    }

    public function verify(string $payload): bool
    {
        if (empty($payload)) {
            return false;
        }

        $decoded = json_decode(base64_decode($payload, true) ?: '', true);

        if (! is_array($decoded) || ! isset($decoded['challenge'], $decoded['solution'])) {
            return false;
        }

        $altcha = $this->altcha();
        $pbkdf2 = new Pbkdf2();

        try {
            $challenge = new Challenge(
                ChallengeParameters::fromArray($decoded['challenge']['parameters'] ?? []),
                $decoded['challenge']['signature'] ?? null
            );

            $solution = new Solution(
                counter: (int) ($decoded['solution']['counter'] ?? 0),
                derivedKey: (string) ($decoded['solution']['derivedKey'] ?? '')
            );

            $result = $altcha->verifySolution(new VerifySolutionOptions(
                payload: new Payload($challenge, $solution),
                algorithm: $pbkdf2,
            ));

            if (! $result->verified) {
                $this->logger->warning('AltchaCaptcha: verification failed', [
                    'expired' => $result->expired,
                    'invalidSignature' => $result->invalidSignature,
                    'invalidSolution' => $result->invalidSolution,
                ]);

                return false;
            }

            // Prevent replay attacks: each challenge may only be verified once.
            // The challenge signature is unique per challenge, so we use it as
            // a single-use token key. Cache::add() returns false if the key
            // already exists (i.e. this challenge was already consumed).
            $key = 'dvdkon-altcha-captcha.challenge.' . hash('sha256', $challenge->signature ?? '');
            $expiry = (int) $this->settings->get('dvdkon-altcha-captcha.expiry', 300);
            $expiry = $expiry > 0 ? $expiry : 300;

            if (! $this->cache->add($key, true, $expiry)) {
                $this->logger->warning('AltchaCaptcha: challenge reused (possible replay attack).');

                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('AltchaCaptcha verification error: ' . $e->getMessage());

            return false;
        }
    }

    public function shouldProtect(string $action): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $setting = $this->settings->get('dvdkon-altcha-captcha.protect_' . $action);

        return (bool) $setting;
    }

    private function altcha(): Altcha
    {
        return new Altcha(
            hmacSignatureSecret: $this->getSecret()
        );
    }

    private function getSecret(): string
    {
        $secret = $this->settings->get('dvdkon-altcha-captcha.hmac_secret');

        if (! empty($secret)) {
            return $secret;
        }

        // No secret configured: generate a long random one and persist it so
        // challenges remain consistent across requests.
        $secret = bin2hex(random_bytes(32));
        $this->settings->set('dvdkon-altcha-captcha.hmac_secret', $secret);

        return $secret;
    }
}
