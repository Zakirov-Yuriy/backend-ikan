<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/* Форма жилого комплекса: название и адрес (slug) */
class ComplexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /* Пустой адрес собирается из названия: L1 -> l1 */
    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));
        $this->merge(['slug' => Str::slug($slug !== '' ? $slug : (string) $this->input('name'))]);
    }

    public function rules(): array
    {
        $id = $this->route('complex')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('complexes', 'slug')->ignore($id)],
        ];
    }

    public function attributes(): array
    {
        return ['name' => '«название»', 'slug' => '«адрес»'];
    }
}
