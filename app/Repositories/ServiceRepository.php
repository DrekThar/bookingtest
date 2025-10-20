<?php

namespace App\Repositories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository
{
    /**
     * Находит услугу по ее ID или выбрасывает исключение, если она не найдена.
     * @param int $id
     * @return Service
     */
    public function findOrFail(int $id): Service
    {
        return Service::findOrFail($id);
    }

    /**
     * Возвращает коллекцию моделей, выбирая только указанные колонки.
     * @param array $columns
     * @return Collection
     */
    public function all(array $columns = ['*']): Collection
    {
        return Service::all($columns);
    }
}
