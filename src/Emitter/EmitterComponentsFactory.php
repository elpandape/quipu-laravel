<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Emitter;

use ElPandaPe\Quipu\Signer\XmlSecSigner;
use ElPandaPe\Quipu\Ws\BillConsultClient;
use ElPandaPe\Quipu\Ws\SoapSender;
use ElPandaPe\Quipu\Xml\CompositeBuilder;
use ElPandaPe\QuipuLaravel\Certificate\CertificateResolver;
use ElPandaPe\QuipuLaravel\Exception\EmitterConfigException;

/**
 * Builds the Lite EmitterComponents from a resolved EmitterConfig: the UBL
 * builder, the xmldsig signer loaded from the PEM certificate (obtained through
 * the CertificateResolver, so its source — path/inline/disk — stays out of
 * here), the SOAP sender pointed at the right SUNAT billService and the
 * billConsultService client used to re-fetch a comprobante's CDR — all with the
 * emitter's SOL credentials. Kept separate from the emitter factories so the
 * Lite and Pro compositions reuse identical component wiring.
 */
final readonly class EmitterComponentsFactory
{
    public function __construct(private CertificateResolver $certificateResolver) {}

    public function make(EmitterConfig $config): EmitterComponents
    {
        return new EmitterComponents(
            builder: new CompositeBuilder(),
            signer: new XmlSecSigner($this->certificatePem($config)),
            sender: new SoapSender(
                $config->billServiceEndpoint(),
                $config->soapUsername(),
                $config->solPass,
                verifyTls: $config->verifyTls,
            ),
            cpeStatusService: new BillConsultClient(
                $config->consultServiceEndpoint(),
                $config->soapUsername(),
                $config->solPass,
                verifyTls: $config->verifyTls,
            ),
        );
    }

    /**
     * The PEM the signer needs: an X.509 certificate plus a passphrase-less
     * private key. A multi-tenant resolver supplies the active tenant's own PEM
     * on the EmitterConfig, which takes precedence; otherwise the
     * CertificateResolver returns the PEM from its configured (global) source.
     * When the key is encrypted, decrypt it here with the passphrase (the Lite
     * signer only accepts a passphrase-less key; loading a .pfx is a Pro
     * capability of a later phase).
     */
    private function certificatePem(EmitterConfig $config): string
    {
        $pem = $config->certificatePem ?? $this->certificateResolver->resolvePem();
        $passphrase = $config->certificatePassphrase;

        if ($passphrase === null) {
            return $pem;
        }

        return $this->decryptPrivateKey($pem, $passphrase);
    }

    private function decryptPrivateKey(string $pem, string $passphrase): string
    {
        $key = openssl_pkey_get_private($pem, $passphrase);

        if ($key === false) {
            throw new EmitterConfigException('No se pudo descifrar la llave privada del certificado con la passphrase configurada.');
        }

        $decrypted = '';
        openssl_pkey_export($key, $decrypted);
        $privateKey = is_string($decrypted) ? $decrypted : '';

        preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $pem, $matches);

        return $privateKey . ($matches[0] ?? '');
    }
}
