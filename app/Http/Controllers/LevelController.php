<?php

namespace App\Http\Controllers;

use App\Models\MembershipLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LevelController extends Controller
{
    public function index()
    {
        return view('membership.catalog', ['kind' => 'levels', 'items' => MembershipLevel::withCount('members')->orderBy('fee')->get(), 'editing' => new MembershipLevel]);
    }

    public function edit(MembershipLevel $level)
    {
        return view('membership.catalog', ['kind' => 'levels', 'items' => MembershipLevel::withCount('members')->orderBy('fee')->get(), 'editing' => $level]);
    }

    private function data(Request $request, ?MembershipLevel $level = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('membership_levels')->ignore($level?->id)], 'fee' => 'required|numeric|decimal:0,2|min:0|max:99999999.99', 'validity_months' => 'required|integer|min:1|max:120', 'benefits' => 'nullable|string|max:4000']);
    }

    public function store(Request $request)
    {
        MembershipLevel::create($this->data($request));

        return redirect()->route('levels.index')->with('success', 'Level added.');
    }

    public function update(Request $request, MembershipLevel $level)
    {
        $level->update($this->data($request, $level));

        return redirect()->route('levels.index')->with('success', 'Level updated. Existing transaction fees and validity are unchanged.');
    }

    public function destroy(MembershipLevel $level)
    {
        DB::transaction(function () use ($level) {
            $level = MembershipLevel::lockForUpdate()->findOrFail($level->id);
            if ($level->members()->exists() || $level->transactions()->exists()) {
                throw ValidationException::withMessages(['level' => 'A level assigned to members or transaction history cannot be deleted.']);
            } $level->delete();
        });

        return redirect()->route('levels.index')->with('success', 'Level deleted.');
    }
}
