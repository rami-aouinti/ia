<?php

declare(strict_types=1);

namespace App;

use JsonException;
use RuntimeException;

/**
 * Durable, file-based idempotency boundary for the simulated publishing handover.
 * The lock protects concurrent CLI runs; the temporary file + rename avoids
 * exposing a partially written JSON payload.
 */
final class HandoverStore
{
    public function __construct(private readonly string $directory) {}

    /**
     * @return array{created: bool, payload: array}
     * @throws JsonException
     */
    public function persistOnce(array $payload): array
    {
        if (($payload['status'] ?? null) !== 'handed_over'
            || ($payload['handover_mode'] ?? null) !== 'SIMULATED_ONLY'
            || empty($payload['content_id'])
            || empty($payload['idempotency_key'])) {
            throw new RuntimeException('Invalid simulated handover payload.');
        }

        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new RuntimeException('Cannot create handover storage directory.');
        }

        $path = $this->directory . '/handover-' . basename((string) $payload['content_id']) . '.json';
        $lock = fopen($path . '.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Cannot open handover lock.');
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Cannot lock handover storage.');
            }

            if (is_file($path)) {
                $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                if (($existing['idempotency_key'] ?? null) !== $payload['idempotency_key']) {
                    throw new RuntimeException('A different version was already handed over; manual review is required.');
                }

                return ['created' => false, 'payload' => $existing];
            }

            $temporaryPath = tempnam($this->directory, '.handover-');
            if ($temporaryPath === false) {
                throw new RuntimeException('Cannot create temporary handover file.');
            }

            try {
                $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                if (file_put_contents($temporaryPath, $json . PHP_EOL, LOCK_EX) === false
                    || !rename($temporaryPath, $path)) {
                    throw new RuntimeException('Cannot persist handover payload.');
                }
            } finally {
                if (is_file($temporaryPath)) {
                    unlink($temporaryPath);
                }
            }

            return ['created' => true, 'payload' => $payload];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
