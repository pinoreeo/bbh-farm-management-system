<?php

namespace App\Services;

use App\Models\Animal;
use Carbon\Carbon;
use DateTimeInterface;

class CertificatePayloadSnapshotService
{
    public function build(array $data, ?Animal $animal = null): string
    {
        $payload = [
            'animal_birth_date' => $this->date($animal?->birth_date),
            'animal_breed_id' => $animal?->breed_id,
            'animal_breed_name' => $animal?->breed?->breed_name,
            'animal_generation' => $animal?->generation,
            'animal_sex' => $animal?->sex,
            'animal_tag_number' => $animal?->tag_number,
            'barcode_format' => $data['barcode_format'] ?? null,
            'barcode_value' => $data['barcode_value'] ?? null,
            'birth_event_id' => $data['birth_event_id'] ?? null,
            'cause_of_death' => $data['cause_of_death'] ?? null,
            'certificate_number' => $data['certificate_number'],
            'certificate_type_id' => $data['certificate_type_id'],
            'death_date' => $this->date($data['death_date'] ?? null),
            'death_time' => $this->time($data['death_time'] ?? null),
            'issue_date' => $this->date($data['issue_date']),
            'issue_place' => $data['issue_place'] ?? null,
            'valid_from' => $this->date($data['valid_from'] ?? null),
            'valid_until' => $this->date($data['valid_until'] ?? null),
            'verification_token' => $data['verification_token'],
            'animal_id' => $data['animal_id'],
        ];

        ksort($payload);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode certificate payload snapshot.');
        }

        return $json;
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        return Carbon::parse($value)->toDateString();
    }

    private function time(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('H:i:s');
        }

        return Carbon::parse($value)->format('H:i:s');
    }
}
