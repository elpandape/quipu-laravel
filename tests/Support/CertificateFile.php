<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Tests\Support;

use RuntimeException;

/**
 * Provides certificate paths for the tests. These are self-signed fixtures
 * copied from Lite and Pro, versioned here so the suite runs on a standalone
 * clone: reaching for a sibling checkout only ever worked inside the monorepo.
 */
final class CertificateFile
{
    /** Password of Pro's versioned test .pfx fixture. */
    public const string PFX_PASSWORD = 'quipu-test';

    /** Path to Lite's passphrase-less test certificate (X.509 + private key PEM). */
    public static function plain(): string
    {
        return __DIR__ . '/../Fixtures/certificate.pem';
    }

    /** Raw bytes of Pro's versioned test .pfx (PKCS#12, RUC 20000000001). */
    public static function pfxBytes(): string
    {
        $bytes = file_get_contents(__DIR__ . '/../Fixtures/certificate.pfx');
        if ($bytes === false) {
            throw new RuntimeException('No se pudo leer el .pfx de prueba de Pro.');
        }

        return $bytes;
    }

    /** The raw PEM contents of Lite's passphrase-less test certificate. */
    public static function plainPem(): string
    {
        $pem = file_get_contents(self::plain());
        if ($pem === false) {
            throw new RuntimeException('No se pudo leer el certificado de prueba de Lite.');
        }

        return $pem;
    }

    /** Lite's test certificate PEM, base64-encoded, for the inline source. */
    public static function plainBase64(): string
    {
        return base64_encode(self::plainPem());
    }

    /** A path that does not point to any file, for the "certificate missing" case. */
    public static function missing(): string
    {
        return sys_get_temp_dir() . '/quipu-inexistente-' . uniqid() . '.pem';
    }

    /** Writes a temporary file whose contents are not a valid certificate. */
    public static function invalid(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'quipu-cert-bad-');
        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del certificado.');
        }

        file_put_contents($path, 'esto no es un certificado PEM');

        return $path;
    }

    /**
     * Writes a temporary PEM whose private key is encrypted with $passphrase
     * (reusing Lite's test key and certificate) and returns its path, to
     * exercise the passphrase branch of the emitter factory.
     */
    public static function encrypted(string $passphrase): string
    {
        $plain = file_get_contents(self::plain());
        if ($plain === false) {
            throw new RuntimeException('No se pudo leer el certificado de prueba de Lite.');
        }

        $key = openssl_pkey_get_private($plain);
        if ($key === false) {
            throw new RuntimeException('El certificado de prueba de Lite no contiene una llave privada válida.');
        }

        $encryptedKey = '';
        if (!openssl_pkey_export($key, $encryptedKey, $passphrase) || !is_string($encryptedKey)) {
            throw new RuntimeException('No se pudo cifrar la llave privada de prueba.');
        }

        if (preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $plain, $matches) !== 1) {
            throw new RuntimeException('El certificado de prueba de Lite no contiene un certificado X.509.');
        }

        $path = tempnam(sys_get_temp_dir(), 'quipu-cert-');
        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del certificado.');
        }

        file_put_contents($path, $encryptedKey . $matches[0]);

        return $path;
    }
}
