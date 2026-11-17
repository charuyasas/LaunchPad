<?php

namespace App\Dashboard\Modules\Sites\UseCases;

final class ListReleaseTagsInteractor
{
    public function __construct(private DeployPipeline $pipeline) {}

    /**
     * @return list<string>
     *
     * @throws DeployFailed
     */
    public function execute(): array
    {
        return ReleaseTags::newestFirst($this->pipeline->tags());
    }
}
