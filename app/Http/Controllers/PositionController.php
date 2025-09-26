<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::orderBy('name')->paginate(12);
        return view('positions.index', compact('positions'));
    }

    public function create()
    {
        return view('positions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:positions,slug',
            'active' => 'boolean'
        ]);
        Position::create($data);
        return redirect()->route('positions.index')->with('ok','Позицію додано');
    }

    public function edit(Position $position)
    {
        return view('positions.edit', compact('position'));
    }

    public function update(Request $request, Position $position)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:positions,slug,'.$position->id,
            'active' => 'boolean'
        ]);
        $position->update($data);
        return redirect()->route('positions.index')->with('ok','Оновлено');
    }

    public function destroy(Position $position)
    {
        $position->delete();
        return back()->with('ok','Видалено');
    }
}
