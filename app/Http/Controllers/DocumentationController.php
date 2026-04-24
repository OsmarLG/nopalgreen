<?php

namespace App\Http\Controllers;

use App\Services\DocumentationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function __construct(private DocumentationService $documentationService) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('documentation/index', [
            'filters' => [
                'search' => $search,
            ],
            'groups' => $this->documentationService->groupedEntriesFor($request->user(), $search),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        return Inertia::render('documentation/show', [
            'entry' => $this->documentationService->findVisibleEntry($request->user(), $slug),
        ]);
    }
}
