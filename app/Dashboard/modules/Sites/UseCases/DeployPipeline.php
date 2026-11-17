<?php

namespace App\Dashboard\Modules\Sites\UseCases;

/**
 * Where deploys come from: the Paylance repository's tags and its
 * "Deploy to VPS" workflow.
 */
interface DeployPipeline
{
    public function isConfigured(): bool;

    /**
     * Every tag in the repository, in no particular order.
     *
     * @return list<string>
     *
     * @throws DeployFailed
     */
    public function tags(): array;

    /**
     * Start the deploy workflow on `$ref` with the given inputs.
     *
     * @param  array<string, string>  $inputs
     *
     * @throws DeployFailed
     */
    public function dispatch(string $ref, array $inputs): void;

    /** A page where people can follow the workflow's runs. */
    public function runsUrl(): string;
}
