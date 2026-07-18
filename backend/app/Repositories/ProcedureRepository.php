<?php

namespace App\Repositories;

use App\Models\Procedure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProcedureRepository
{
    public function paginate(?string $search, int $perPage): LengthAwarePaginator
    {
        return Procedure::query()
            ->when($search, fn ($query, string $search) => $query->where('procedure', 'like', "%{$search}%"))
            ->orderBy('procedure')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Procedure
    {
        return Procedure::create($data);
    }

    public function update(Procedure $procedure, array $data): Procedure
    {
        $procedure->update($data);

        return $procedure->refresh();
    }

    public function delete(Procedure $procedure): void
    {
        $procedure->delete();
    }
}
