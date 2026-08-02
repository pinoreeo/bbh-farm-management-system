<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

class AnimalUpdateRequest extends AnimalStoreRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $animalId = $this->route('animal')?->id;

        return [
            'tag_number' => ['sometimes', 'string', 'max:100', Rule::unique('animals', 'tag_number')->ignore($animalId)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'breed_id' => ['sometimes', 'integer', 'exists:animal_breeds,id'],
            'sex' => ['sometimes', 'in:male,female'],
            'generation' => ['sometimes', Rule::in(self::GENERATION_OPTIONS)],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'current_pen_id' => ['nullable', 'integer', 'exists:animal_pens,id'],
            'reproductive_status' => ['nullable', Rule::in(self::REPRODUCTIVE_STATUS_OPTIONS)],
            'status_date' => ['nullable', 'date'],
            'life_status' => ['sometimes', 'in:alive,dead'],
            'exit_status' => ['nullable', Rule::in(self::EXIT_STATUS_OPTIONS)],
            'notes' => ['nullable', 'string'],
            'is_impor' => ['sometimes', 'boolean'],
            'origin_type' => ['nullable', Rule::in(self::ORIGIN_TYPE_OPTIONS)],
            'origin_detail' => ['nullable', 'string', 'max:255'],
        ];
    }
}
