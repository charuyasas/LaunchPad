<?php

namespace App\Dashboard\Modules\Sites\IO\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeploySiteRequest extends FormRequest
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['ref' => 'tag'];
    }
}
