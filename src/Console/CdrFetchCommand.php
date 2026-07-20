<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console;

use ElPandaPe\Quipu\Exception\TransportException;
use ElPandaPe\Quipu\Quipu;
use ElPandaPe\QuipuLaravel\Emitter\EmitterConfigResolver;
use ElPandaPe\QuipuLaravel\Models\Document;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Re-downloads a comprobante's CDR from SUNAT (billConsultService getStatusCdr)
 * without resending it, and stores it. The output disk/folder/name honour
 * --disk/--path/--file, defaulting to the configured cdr/ folder.
 */
final class CdrFetchCommand extends QuipuCommand
{
    /** @var string */
    protected $signature = 'quipu:cdr:fetch
        {document : Id del comprobante cuyo CDR se re-descarga}
        {--disk= : Disco donde guardar el CDR}
        {--path= : Carpeta donde guardar el CDR}
        {--file= : Nombre del archivo del CDR}';

    /** @var string */
    protected $description = 'Re-descarga de SUNAT el CDR de un comprobante y lo guarda.';

    public function handle(Quipu $quipu, EmitterConfigResolver $resolver, FilesystemFactory $factory): int
    {
        $reference = $this->argumentString('document');
        $document = Document::query()->find((int) $reference);
        if (!$document instanceof Document) {
            $this->error(sprintf('No se encontró el comprobante #%s.', $reference));

            return self::FAILURE;
        }

        $ruc = $resolver->resolve()->ruc;
        $type = $document->document_type->value;

        try {
            $result = $quipu->retrieveCdr($ruc, $type, $document->series, $document->number);
        } catch (TransportException $exception) {
            $this->error(sprintf('No se pudo consultar el CDR: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $cdr = $result->cdr;
        if (!$cdr instanceof \ElPandaPe\Quipu\Result\CdrResult) {
            $this->warn(sprintf('SUNAT no adjuntó un CDR (estado %s: %s).', $result->statusCode, $result->statusMessage));

            return self::FAILURE;
        }

        $fileName = $this->optionString('file')
            ?? sprintf('R-%s-%s-%s-%d.xml', $ruc, $type, $document->series, $document->number);
        $path = $this->targetFolder($this->configString('quipu.storage.paths.cdr', 'cdr')) . '/' . $fileName;

        $this->resolveDisk($factory)->put($path, (string) $cdr->xml);
        $document->cdr_path = $path;
        $document->save();

        $this->info(sprintf('CDR guardado en %s.', $path));

        return self::SUCCESS;
    }
}
