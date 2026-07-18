<?php

namespace App\Services;

use App\DTOs\ProcedureDTO;
use App\Models\Procedure;
use App\Repositories\ProcedureRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProcedureService
{
    public function __construct(
        private readonly ProcedureRepository $procedures,
    ) {}

    public function list(?string $search, int $perPage, ?bool $active = true): LengthAwarePaginator
    {
        return $this->procedures->paginate($search, $perPage, $active);
    }

    public function create(ProcedureDTO $data): Procedure
    {
        return $this->procedures->create($data->values)->loadCount('attendances');
    }

    public function update(Procedure $procedure, ProcedureDTO $data): Procedure
    {
        return $this->procedures->update($procedure, $data->values)->loadCount('attendances');
    }

    public function changeStatus(Procedure $procedure, bool $active): Procedure
    {
        return $this->procedures->update($procedure, ['is_active' => $active])->loadCount('attendances');
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
