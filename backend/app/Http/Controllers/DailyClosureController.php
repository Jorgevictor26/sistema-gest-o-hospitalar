<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseDayRequest;
use App\Http\Requests\ReopenDayRequest;
use App\Http\Resources\DailyClosureResource;
use App\Models\DailyClosure;
use App\Services\DailyClosureService;
use Illuminate\Http\JsonResponse;

class DailyClosureController extends Controller
{
    public function __construct(private readonly DailyClosureService $closureService) {}

    public function show(string $date): DailyClosureResource
    {
        $closure = $this->closureService->activeForDate($date);
        abort_if($closure === null, 404, 'Não existe fecho activo para esta data.');

        return new DailyClosureResource($closure);
    }

    public function store(CloseDayRequest $request): JsonResponse
    {
        $closure = $this->closureService->close(
            $request->validated('date', today()->toDateString()),
            $request->user(),
        );

        return (new DailyClosureResource($closure))->response()->setStatusCode(201);
    }

    public function status(string $date): JsonResponse
    {
        $active = $this->closureService->activeForDate($date);
        $latest = $active ?? $this->closureService->latestForDate($date);

        return response()->json([
            'date' => $date,
            'is_closed' => $active !== null,
            'closure' => $latest ? new DailyClosureResource($latest) : null,
        ]);
    }

    public function reopen(ReopenDayRequest $request, DailyClosure $dailyClosure): DailyClosureResource
    {
        return new DailyClosureResource(
            $this->closureService->reopen($dailyClosure, $request->validated('reason'), $request->user())
        );
    }
}
