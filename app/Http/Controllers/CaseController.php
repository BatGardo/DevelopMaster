<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\CaseAction;
use App\Models\CaseDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        //$this->authorize('manage-cases');

        $sort = $request->query('sort','created_at');
        $direction = $request->query('direction','desc');

        $query = CaseModel::with(['owner','executor']);

        // фільтур за виконавцем/статусом/діапазоном дат
        if ($exec = $request->query('executor')) $query->where('executor_id',$exec);
        if ($status = $request->query('status')) $query->where('status',$status);

        $cases = $query->orderBy($sort,$direction)->paginate(10)->withQueryString();
        $executors = User::whereIn('role',['executor','admin'])->orderBy('name')->get();

        return view('cases.index', compact('cases','sort','direction','executors'));
    }

    public function create()
    {
        //$this->authorize('manage-cases');
        $executors = User::whereIn('role',['executor','admin'])->orderBy('name')->get();
        return view('cases.create', compact('executors'));
    }

    public function store(Request $request)
    {
        //$this->authorize('manage-cases');
        $data = $request->validate([
            'title'=>'required|string|max:255',
            'description'=>'nullable|string',
            'executor_id'=>'nullable|exists:users,id',
            'claimant_name'=>'nullable|string|max:255',
            'debtor_name'=>'nullable|string|max:255',
            'deadline_at'=>'nullable|date'
        ]);

        $data['user_id'] = $request->user()->id;

        $case = CaseModel::create($data);

        CaseAction::create([
            'case_id'=>$case->id,
            'user_id'=>$request->user()->id,
            'type'=>'created',
            'notes'=>'Створено справу'
        ]);

        // Якщо є завантажені документи
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('cases/'.$case->id, 'public');
                CaseDocument::create([
                    'case_id'=>$case->id,
                    'uploaded_by'=>$request->user()->id,
                    'title'=>$file->getClientOriginalName(),
                    'path'=>$path
                ]);
                CaseAction::create([
                    'case_id'=>$case->id,
                    'user_id'=>$request->user()->id,
                    'type'=>'document_added',
                    'notes'=>$file->getClientOriginalName()
                ]);
            }
        }

        // просте внутрішнє повідомлення (можеш замінити на свою Notification-модель/таблицю)
        session()->flash('ok','Справу створено');
        return redirect()->route('cases.show',$case);
    }

    public function show(CaseModel $case)
    {
        //Gate::authorize('view-case',$case);

        $case->load(['owner','executor','actions.user','documents.uploader']);
        return view('cases.show', compact('case'));
    }

    // додавання дії з екрану справи
    public function addAction(Request $request, CaseModel $case)
    {
        //Gate::authorize('view-case',$case);
        $data = $request->validate([
            'type'=>'required|string|max:50',
            'notes'=>'nullable|string'
        ]);
        $data['case_id']=$case->id;
        $data['user_id']=$request->user()->id;

        CaseAction::create($data);

        return back()->with('ok','Дію додано');
    }

    // завантаження документів з екрану справи
    public function uploadDocument(Request $request, CaseModel $case)
    {
        //Gate::authorize('view-case',$case);

        $request->validate(['file'=>'required|file|max:20480']);
        $path = $request->file('file')->store('cases/'.$case->id,'public');

        CaseDocument::create([
            'case_id'=>$case->id,
            'uploaded_by'=>$request->user()->id,
            'title'=>$request->file('file')->getClientOriginalName(),
            'path'=>$path
        ]);

        CaseAction::create([
            'case_id'=>$case->id,'user_id'=>$request->user()->id,
            'type'=>'document_added','notes'=>$request->file('file')->getClientOriginalName()
        ]);

        return back()->with('ok','Документ збережено');
    }
}
