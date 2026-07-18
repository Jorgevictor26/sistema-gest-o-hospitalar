<?php

namespace App\Services;

use App\Models\Procedure;
use App\Repositories\ProcedureRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProcedureService
{
    public function __construct(
        private readonly ProcedureRepository $procedures,
    ) {}

    public function list(?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->procedures->paginate($search, $perPage);
    }

    public function create(array $data): Procedure
    {
        return $this->procedures->create($data);
    }

    public function update(Procedure $procedure, array $data): Procedure
    {
        return $this->procedures->update($procedure, $data);
    }

    public function delete(Procedure $procedure): void
    {
        if ($procedure->attendances()->exists()) {
            throw new ConflictHttpException(
                'Este procedimento já está associado a atendimentos e não pode ser eliminado.'
            );
        }

        $this->procedures->delete($procedure);
    }
}
