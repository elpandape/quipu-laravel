<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console\Pro;

use ElPandaPe\QuipuLaravel\Models\Document;
use ElPandaPe\QuipuLaravel\Pro\ProDetector;
use ElPandaPe\QuipuPro\Error\Diagnosis;
use ElPandaPe\QuipuPro\Error\ErrorDiagnosis;

/**
 * Explains a SUNAT response/fault code (Pro's ErrorDiagnosis): the official SUNAT
 * message, the severity band and the actionable next step. Diagnoses a raw code
 * argument, or the recorded response code of a persisted document via --id.
 */
final class DiagnoseCommand extends ProCommand
{
    /** @var string */
    protected $signature = 'quipu:diagnose
        {code? : Código de respuesta/fault de SUNAT a diagnosticar}
        {--id= : Diagnostica el código de respuesta de un comprobante persistido}';

    /** @var string */
    protected $description = 'Diagnostica un código de rechazo de SUNAT con la acción y el remedio recomendados.';

    public function handle(ProDetector $detector): int
    {
        if ($this->guardPro($detector)) {
            return self::FAILURE;
        }

        $code = $this->resolveCode();
        if ($code === null) {
            return self::FAILURE;
        }

        $this->render(new ErrorDiagnosis()->diagnose($code));

        return self::SUCCESS;
    }

    private function resolveCode(): ?string
    {
        $code = $this->argumentString('code');
        if ($code !== '') {
            return $code;
        }

        $id = $this->optionString('id');
        if ($id === null) {
            $this->error('Indique un código a diagnosticar o un comprobante con --id.');

            return null;
        }

        $document = Document::query()->find((int) $id);
        if (!$document instanceof Document) {
            $this->error(sprintf('No se encontró el comprobante #%s.', $id));

            return null;
        }

        $responseCode = $document->sunat_response_code;
        if (!is_string($responseCode) || $responseCode === '') {
            $this->error(sprintf('El comprobante #%s aún no tiene un código de respuesta de SUNAT.', $id));

            return null;
        }

        return $responseCode;
    }

    private function render(Diagnosis $diagnosis): void
    {
        $this->info(sprintf('Código %d — %s', $diagnosis->code, $diagnosis->severity->value));
        $this->line(sprintf('SUNAT: %s', $diagnosis->sunatMessage ?? '(sin mensaje en el catálogo)'));
        $this->line(sprintf('Acción: %s', $diagnosis->action));
        $this->line(sprintf('Remedio: %s', $diagnosis->remedy));
        $this->line(sprintf('Reintentable: %s', $diagnosis->retryable ? 'sí' : 'no'));
    }
}
