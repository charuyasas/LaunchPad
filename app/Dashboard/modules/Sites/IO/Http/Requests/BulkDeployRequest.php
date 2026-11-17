<?php

namespace App\Dashboard\Modules\Sites\IO\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeployRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ref' => ['required', 'string', 'max:255'],
            'run_tests' => ['sometimes', 'boolean'],
            'sites' => ['required', 'array', 'min:1', 'max:50'],
            'sites.*' => ['required', 'string', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['sites.required' => 'Choose at least one site to deploy.'];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['ref' => 'tag'];
    }

    /**
     * @return list<string>
     */
    public function siteIds(): array
    {
        return array_values(array_map('strval', (array) $this->input('sites', [])));
    }
}
