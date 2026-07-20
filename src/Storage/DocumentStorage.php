<?php

declare(strict_types=1);

namespace ElPandaPe\QuipuLaravel\Storage;

use ElPandaPe\QuipuLaravel\Exception\DocumentStorageException;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Reads and writes document files on a Laravel filesystem disk. The disk comes
 * from config('quipu.storage.disk') — local, s3, or any other; S3 needs no
 * special handling, it is just a disk. Files are laid out under the logical
 * signed/, cdr/ and inbox/ folders.
 */
final readonly class DocumentStorage
{
    public function __construct(
        private Filesystem $disk,
        private StoragePaths $paths,
    ) {}

    /** Store a signed XML under signed/ and return its disk-relative path. */
    public function putSignedXml(string $filename, string $contents): string
    {
        return $this->put($this->paths->signed, $filename, $contents);
    }

    /** Read a signed XML back by its disk-relative path. */
    public function getSignedXml(string $path): string
    {
        return $this->read($path);
    }

    /** Store a CDR under cdr/ and return its disk-relative path. */
    public function putCdr(string $filename, string $contents): string
    {
        return $this->put($this->paths->cdr, $filename, $contents);
    }

    /** Read a CDR back by its disk-relative path. */
    public function getCdr(string $path): string
    {
        return $this->read($path);
    }

    /**
     * List the files sitting in the inbox/ folder, as disk-relative paths.
     *
     * @return list<string>
     */
    public function listInbox(): array
    {
        return array_values($this->disk->files($this->paths->inbox));
    }

    /** Read an inbox file by its bare filename. */
    public function readInbox(string $filename): string
    {
        return $this->read($this->join($this->paths->inbox, $filename));
    }

    private function put(string $folder, string $filename, string $contents): string
    {
        $path = $this->join($folder, $filename);
        $this->disk->put($path, $contents);

        return $path;
    }

    private function read(string $path): string
    {
        $contents = $this->disk->get($path);

        if ($contents === null) {
            throw new DocumentStorageException(sprintf('No se encontró el archivo "%s" en el disco de almacenamiento.', $path));
        }

        return $contents;
    }

    private function join(string $folder, string $filename): string
    {
        return trim($folder, '/') . '/' . ltrim($filename, '/');
    }
}
