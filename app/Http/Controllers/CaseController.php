<?php

namespace App\Http\Controllers;

use App\Models\CaseAction;
use App\Models\CaseDocument;
use App\Models\CaseModel;
use App\Models\User;
use App\Services\Reports\CasePdfExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('list-cases');

        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');

        $query = CaseModel::with(['owner', 'executor']);

        if ($request->user()->isExecutor()) {
            $query->where('executor_id', $request->user()->id);
        }

        if ($executor = $request->query('executor')) {
            $query->where('executor_id', $executor);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $selectedRegion = null;
        $regionInput = $request->query('region');

        if ($regionInput !== null && $regionInput !== '') {
            if ($regionInput === '__null__') {
                $query->whereNull('region');
                $selectedRegion = '__null__';
            } else {
                $normalizedRegion = Str::of($regionInput)->squish()->title()->value();
                $query->where('region', $normalizedRegion);
                $selectedRegion = $normalizedRegion;
            }
        }

        $cases = $query->orderBy($sort, $direction)->paginate(12)->withQueryString();
        $executors = User::whereIn('role', ['executor', 'admin'])->orderBy('name')->get();
        $regions = CaseModel::regionOptions();
        $hasUnspecifiedRegion = CaseModel::whereNull('region')->exists();

        return view('cases.index', compact(
            'cases',
            'sort',
            'direction',
            'executors',
            'regions',
            'hasUnspecifiedRegion',
            'selectedRegion'
        ));
    }

    public function mine(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isExecutor()) {
            return redirect()->route('cases.index');
        }

        $query = CaseModel::with(['executor', 'owner'])
            ->orderByDesc('created_at');

        if ($user->isApplicant()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isViewer()) {
            // viewers can see everything but in read-only mode
        }

        $cases = $query->paginate(12)->withQueryString();

        return view('cases.mine', compact('cases'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create-case');

        $executors = User::whereIn('role', ['executor', 'admin'])->orderBy('name')->get();
        $regionOptions = CaseModel::regionOptions();

        return view('cases.create', compact('executors', 'regionOptions'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create-case');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'executor_id' => ['nullable', 'exists:users,id'],
            'region' => ['nullable', 'string', 'max:120'],
            'claimant_name' => ['nullable', 'string', 'max:255'],
            'debtor_name' => ['nullable', 'string', 'max:255'],
            'deadline_at' => ['nullable', 'date'],
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status'] = 'new';

        $case = CaseModel::create($data);

        CaseAction::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'type' => 'created',
            'notes' => __('Case created'),
        ]);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('cases/' . $case->id, 'public');
                $document = CaseDocument::create([
                    'case_id' => $case->id,
                    'uploaded_by' => $request->user()->id,
                    'title' => $file->getClientOriginalName(),
                    'path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);

                CaseAction::create([
                    'case_id' => $case->id,
                    'user_id' => $request->user()->id,
                    'type' => 'document_added',
                    'notes' => $document->title,
                ]);
            }
        }

        return redirect()->route('cases.show', $case)->with('ok', __('Case created successfully.'));
    }

    public function show(Request $request, CaseModel $case)
    {
        Gate::authorize('view-case', $case);

        $case->load(['owner', 'executor', 'actions.user', 'documents.uploader']);

        $canUpdate = Gate::forUser($request->user())->check('update-case', $case);

        return view('cases.show', compact('case', 'canUpdate'));
    }

    public function addAction(Request $request, CaseModel $case)
    {
        Gate::authorize('update-case', $case);

        $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['case_id'] = $case->id;
        $data['user_id'] = $request->user()->id;

        CaseAction::create($data);

        return back()->with('ok', __('Action added successfully.'));
    }

    public function exportPdf(Request $request, CaseModel $case, CasePdfExporter $exporter)
    {
        Gate::authorize('view-case', $case);

        $pdf = $exporter->build($case);
        $filename = sprintf('case-%d-%s.pdf', $case->id, now()->format('Ymd-His'));

        return $pdf->download($filename);
    }

    public function uploadDocument(Request $request, CaseModel $case)
    {
        Gate::authorize('update-case', $case);

        $request->validate(['file' => ['required', 'file', 'max:20480']]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('cases/' . $case->id, 'public');

        $document = CaseDocument::create([
            'case_id' => $case->id,
            'uploaded_by' => $request->user()->id,
            'title' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'file_size' => $uploadedFile->getSize(),
            'mime_type' => $uploadedFile->getMimeType(),
        ]);

        CaseAction::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'type' => 'document_added',
            'notes' => $document->title,
        ]);

        return back()->with('ok', __('Document uploaded successfully.'));
    }
}