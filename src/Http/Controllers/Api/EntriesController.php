<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Wame\LaravelTelescopeDashboard\Http\Requests\SearchEntriesRequest;
use Wame\LaravelTelescopeDashboard\Services\TelescopeQueryService;

class EntriesController extends Controller
{
    public function __construct(
        protected TelescopeQueryService $queryService
    ) {}

    public function index(SearchEntriesRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $result = $this->queryService->search($filters);

        return response()->json($result);
    }

    public function show(string $uuid): JsonResponse
    {
        $entry = $this->queryService->find($uuid);

        if (! $entry) {
            return response()->json(['message' => 'Entry not found'], 404);
        }

        return response()->json($entry);
    }

    public function showWithBatch(string $uuid): JsonResponse
    {
        $result = $this->queryService->findWithBatch($uuid);

        if (! $result) {
            return response()->json(['message' => 'Entry not found'], 404);
        }

        return response()->json($result);
    }

    public function filters(Request $request, string $type): JsonResponse
    {
        $values = $this->queryService->getFilterValues($type);

        return response()->json($values);
    }
}
