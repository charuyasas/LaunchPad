<?php

namespace App\Dashboard\Modules\Sites\UseCases;

use App\Dashboard\Modules\Sites\Entities\Site;

interface SiteRepository
{
    /**
     * @return list<Site>
     */
    public function all(): array;

    public function find(string $id): ?Site;

    public function save(Site $site): void;

    public function delete(string $id): void;
}
