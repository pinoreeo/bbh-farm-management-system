<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

class AnimalStoreRequest extends ApiRequest
{
    public const GENERATION_OPTIONS = ['F1', 'F2', 'F3', 'F4', 'F5', 'Pure Breed'];

    public const REPRODUCTIVE_STATUS_OPTIONS = ['kosong', 'kawin', 'bunting', 'kering', 'laktasi_kosong', 'melahirkan', 'afkir'];

    public const EXIT_STATUS_OPTIONS = ['sold', 'culled', 'lost'];

    public const ORIGIN_TYPE_OPTIONS = ['internal_birth', 'purchase', 'import', 'grant', 'unknown'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tag_number' => ['nullable', 'string', 'max:100', 'unique:animals,tag_number'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'breed_id' => ['required', 'integer', 'exists:animal_breeds,id'],
            'sex' => ['required', 'in:male,female'],
            'generation' => ['required', Rule::in(self::GENERATION_OPTIONS)],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'current_pen_id' => ['nullable', 'integer', 'exists:animal_pens,id'],
            'reproductive_status' => ['nullable', Rule::in(self::REPRODUCTIVE_STATUS_OPTIONS)],
            'status_date' => ['nullable', 'date'],
            'life_status' => ['nullable', 'in:alive,dead'],
            'exit_status' => ['nullable', Rule::in(self::EXIT_STATUS_OPTIONS)],
            'notes' => ['nullable', 'string'],
            'is_impor' => ['nullable', 'boolean'],
            'origin_type' => ['nullable', Rule::in(self::ORIGIN_TYPE_OPTIONS)],
            'origin_detail' => ['nullable', 'string', 'max:255'],
        ];
    }
}
