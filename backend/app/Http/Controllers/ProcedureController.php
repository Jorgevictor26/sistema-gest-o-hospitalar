<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProcedureRequest;
use App\Http\Requests\UpdateProcedureRequest;
use App\Http\Resources\ProcedureResource;
use App\Models\Procedure;
use App\Services\ProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProcedureController extends Controller
{
    public function __construct(
        private readonly ProcedureService $procedureService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $procedures = $this->procedureService->list(
            $filters['search'] ?? null,
            $filters['per_page'] ?? 15,
        );

        return ProcedureResource::collection($procedures);
    }

    public function store(StoreProcedureRequest $request): JsonResponse
    {
        return (new ProcedureResource(
            $this->procedureService->create($request->validated())
        ))->response()->setStatusCode(201);
    }

    public function show(Procedure $procedure): ProcedureResource
    {
        return new ProcedureResource($procedure);
    }

    public function update(UpdateProcedureRequest $request, Procedure $procedure): ProcedureResource
    {
        return new ProcedureResource(
            $this->procedureService->update($procedure, $request->validated())
        );
    }

    public function destroy(Procedure $procedure): Response
    {
        $this->procedureService->delete($procedure);

        return response()->noContent();
    }
}
