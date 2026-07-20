<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Console;

use ElPandaPe\Quipu\Quipu;
use ElPandaPe\QuipuLaravel\Models\Ticket;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Signs and sends a prepared daily summary or comunicación de baja to SUNAT and
 * records the ticket to poll. The XML is read from the inbox by default (or the
 * disk/folder/file given by --disk/--path/--file), parsed back into its model,
 * re-signed and submitted.
 */
final class SummaryCommand extends QuipuCommand
{
    /** @var string */
    protected $signature = 'quipu:summary
        {--file= : Archivo XML del resumen/baja a enviar}
        {--disk= : Disco donde vive el archivo}
        {--path= : Carpeta donde vive el archivo}';

    /** @var string */
    protected $description = 'Envía a SUNAT un resumen diario o comunicación de baja preparado y guarda su ticket.';

    public function handle(Quipu $quipu, FilesystemFactory $factory): int
    {
        $file = $this->optionString('file');
        if ($file === null) {
            $this->error('Indique el archivo del resumen con --file.');

            return self::FAILURE;
        }

        $path = $this->targetFolder($this->configString('quipu.storage.paths.inbox', 'inbox')) . '/' . $file;
        $xml = $this->resolveDisk($factory)->get($path);
        if ($xml === null) {
            $this->error(sprintf('No se encontró el archivo "%s".', $path));

            return self::FAILURE;
        }

        $document = $quipu->read($xml);
        $ticket = $quipu->sendSummary($quipu->sign($document));

        Ticket::query()->create([
            'ticket' => $ticket->ticket,
            'document_type' => $document->documentType(),
            'state' => Ticket::STATE_PENDING,
        ]);

        $this->info(sprintf('Resumen enviado. Ticket: %s.', $ticket->ticket));

        return self::SUCCESS;
    }
}
