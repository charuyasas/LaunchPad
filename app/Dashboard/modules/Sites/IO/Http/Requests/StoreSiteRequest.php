<?php

namespace App\Dashboard\Modules\Sites\IO\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80'],
            'base_url' => ['required', 'string', 'url', 'starts_with:https://', 'max:255'],
            'version_path' => ['nullable', 'string', 'max:200', 'regex:/^\/?[A-Za-z0-9._~\/%-]+$/'],
            'client' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'base_url.url' => 'Enter a full address, like https://app.example.com.',
            'base_url.starts_with' => 'The base URL must start with https://.',
            'version_path.regex' => 'Use a plain path, like /version or api/version.',
            'client.regex' => 'Use the folder name only, like paylance or rasangi.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'site name',
            'base_url' => 'base URL',
            'version_path' => 'version endpoint',
            'client' => 'client folder',
        ];
    }

    public function client(): ?string
    {
        $client = $this->input('client');

        return is_string($client) && trim($client) !== '' ? trim($client) : null;
    }

    public function versionPath(): ?string
    {
        $path = $this->input('version_path');

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return '/'.ltrim(trim($path), '/');
    }
}
