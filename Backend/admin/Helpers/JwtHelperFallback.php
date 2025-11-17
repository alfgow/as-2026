<?php

declare(strict_types=1);

namespace Firebase\JWT;

use InvalidArgumentException;
use UnexpectedValueException;

if (!class_exists(__NAMESPACE__ . '\\JWT')) {
    class JWT
    {
        /**
         * Extra leeway in seconds to account for clock skew.
         */
        public static int $leeway = 0;

        /**
         * Encode a payload as a signed JWT string.
         *
         * @param array  $payload
         * @param string $key
         * @param string $alg
         */
        public static function encode(array $payload, string $key, string $alg = 'HS256'): string
        {
            $header = ['typ' => 'JWT', 'alg' => $alg];
            $segments = [];
            $segments[] = self::urlsafeB64Encode(self::jsonEncode($header));
            $segments[] = self::urlsafeB64Encode(self::jsonEncode($payload));
            $signingInput = implode('.', $segments);
            $signature = self::sign($signingInput, $key, $alg);
            $segments[] = self::urlsafeB64Encode($signature);

            return implode('.', $segments);
        }

        /**
         * Decode and validate a JWT string.
         */
        public static function decode(string $jwt, Key $key): object
        {
            $tks = explode('.', $jwt);
            if (count($tks) !== 3) {
                throw new UnexpectedValueException('Wrong number of segments');
            }

            [$headb64, $bodyb64, $cryptob64] = $tks;
            $header  = self::jsonDecode(self::urlsafeB64Decode($headb64));
            $payload = self::jsonDecode(self::urlsafeB64Decode($bodyb64));
            $sig     = self::urlsafeB64Decode($cryptob64);

            $alg = $header->alg ?? null;
            if (!is_string($alg) || $alg === '') {
                throw new UnexpectedValueException('Empty algorithm');
            }

            if (strcasecmp($alg, $key->getAlgorithm()) !== 0) {
                throw new UnexpectedValueException('Algorithm mismatch');
            }

            $expected = self::sign("{$headb64}.{$bodyb64}", $key->getKeyMaterial(), $alg);
            if (!hash_equals($expected, $sig)) {
                throw new UnexpectedValueException('Signature verification failed');
            }

            self::assertTimestamps($payload);

            return $payload;
        }

        private static function sign(string $input, string $key, string $alg): string
        {
            switch ($alg) {
                case 'HS256':
                    return hash_hmac('sha256', $input, $key, true);
                default:
                    throw new InvalidArgumentException('Algorithm not supported');
            }
        }

        private static function urlsafeB64Encode(string $input): string
        {
            return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
        }

        private static function urlsafeB64Decode(string $input): string
        {
            $remainder = strlen($input) % 4;
            if ($remainder) {
                $input .= str_repeat('=', 4 - $remainder);
            }

            $decoded = base64_decode(strtr($input, '-_', '+/'), true);
            if ($decoded === false) {
                throw new UnexpectedValueException('Invalid base64 string');
            }

            return $decoded;
        }

        /**
         * @param array<string, mixed> $data
         */
        private static function jsonEncode(array $data): string
        {
            $json = json_encode($data, JSON_UNESCAPED_SLASHES);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new UnexpectedValueException('JSON encoding error: ' . json_last_error_msg());
            }

            return $json;
        }

        private static function jsonDecode(string $json): object
        {
            $data = json_decode($json, false);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new UnexpectedValueException('JSON decoding error: ' . json_last_error_msg());
            }

            if (is_array($data)) {
                $data = (object)$data;
            }

            if (!is_object($data)) {
                throw new UnexpectedValueException('Invalid JWT segment');
            }

            return $data;
        }

        private static function assertTimestamps(object $payload): void
        {
            $timestamp = time();

            if (isset($payload->nbf) && is_numeric($payload->nbf) && ((int)$payload->nbf > $timestamp + self::$leeway)) {
                throw new UnexpectedValueException('Cannot handle token prior to nbf');
            }

            if (isset($payload->iat) && is_numeric($payload->iat) && ((int)$payload->iat > $timestamp + self::$leeway)) {
                throw new UnexpectedValueException('Cannot handle token prior to iat');
            }

            if (isset($payload->exp) && is_numeric($payload->exp) && (($timestamp - self::$leeway) >= (int)$payload->exp)) {
                throw new UnexpectedValueException('Expired token');
            }
        }
    }
}

if (!class_exists(__NAMESPACE__ . '\\Key')) {
    class Key
    {
        private string $keyMaterial;
        private string $algorithm;

        public function __construct(string $keyMaterial, string $algorithm)
        {
            $this->keyMaterial = $keyMaterial;
            $this->algorithm   = $algorithm;
        }

        public function getKeyMaterial(): string
        {
            return $this->keyMaterial;
        }

        public function getAlgorithm(): string
        {
            return $this->algorithm;
        }
    }
}
