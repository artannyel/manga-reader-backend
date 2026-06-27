<?php

declare(strict_types=1);

namespace App\Domains\Mangas\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MangaSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'limit' => ['integer', 'min:1', 'max:100'],
            'offset' => ['integer', 'min:0'],
        ];
    }
}
