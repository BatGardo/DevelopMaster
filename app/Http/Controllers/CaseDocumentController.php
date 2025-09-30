<?php

namespace App\Http\Controllers;

use App\Models\CaseAction;
use App\Models\CaseDocument;
use App\Models\CaseModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CaseDocumentController extends Controller
{
    public function download(Request $request, CaseModel $case, CaseDocument $document)
    {
        Gate::authorize('view-case', $case);
        $this->ensureRelated($case, $document);

        $disk = 'public';
        if (! Storage::disk($disk)->exists($document->path)) {
            abort(404, __('File not found.'));
        }

        return Storage::disk($disk)->download($document->path, $document->title);
    }

    public function update(Request $request, CaseModel $case, CaseDocument $document)
    {
        Gate::authorize('update-case', $case);
        $this->ensureRelated($case, $document);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $originalTitle = $document->title;
        if ($originalTitle === $data['title']) {
            return back()->with('ok', __('No changes were made.'));
        }

        $document->update(['title' => $data['title']]);

        CaseAction::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'type' => 'document_renamed',
            'notes' => __('Renamed :from to :to', ['from' => $originalTitle, 'to' => $document->title]),
        ]);

        return back()->with('ok', __('Document renamed successfully.'));
    }

    public function destroy(Request $request, CaseModel $case, CaseDocument $document)
    {
        Gate::authorize('update-case', $case);
        $this->ensureRelated($case, $document);

        $disk = 'public';
        Storage::disk($disk)->delete($document->path);

        $title = $document->title;
        $document->delete();

        CaseAction::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'type' => 'document_deleted',
            'notes' => $title,
        ]);

        return back()->with('ok', __('Document removed successfully.'));
    }

    protected function ensureRelated(CaseModel $case, CaseDocument $document): void
    {
        if ($document->case_id !== $case->id) {
            abort(404);
        }
    }
}
