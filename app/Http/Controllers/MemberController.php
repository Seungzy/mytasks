<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|integer', 'level' => 'nullable|integer']);
        $members = Member::with('category', 'level')->withExists(['transactions as membership_active' => fn ($q) => $q->where('status', 'approved')->whereDate('valid_from', '<=', today())->whereDate('valid_until', '>=', today())])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('membership_number', 'like', "%$term%")
                        ->orWhere(function ($names) use ($term) {
                            foreach (preg_split('/\s+/', trim($term)) as $part) {
                                $names->where(fn ($name) => $name->where('first_name', 'like', "%$part%")->orWhere('last_name', 'like', "%$part%"));
                            }
                        });
                });
            })
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('membership_category_id', $id))->when($filters['level'] ?? null, fn ($q, $id) => $q->where('membership_level_id', $id))->latest('id')->paginate(12)->withQueryString();

        return view('membership.members.index', compact('members') + $this->choices());
    }

    private function choices(): array
    {
        return ['categories' => MembershipCategory::orderBy('name')->get(), 'levels' => MembershipLevel::orderBy('fee')->get()];
    }

    public function create()
    {
        return view('membership.members.form', ['member' => new Member] + $this->choices());
    }

    private function validated(Request $request, ?Member $member = null): array
    {
        return $request->validate(['first_name' => 'required|string|max:100', 'last_name' => 'required|string|max:100', 'email' => ['required', 'email', 'max:255', Rule::unique('members')->ignore($member?->id)], 'phone' => 'nullable|string|max:40', 'address' => 'nullable|string|max:2000', 'membership_category_id' => 'required|exists:membership_categories,id', 'membership_level_id' => 'required|exists:membership_levels,id', 'membership_number' => ['sometimes', 'required', 'string', 'max:40', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique('members')->ignore($member?->id)]]);
    }

    public function store(Request $request, MembershipService $service)
    {
        $data = $this->validated($request);
        $member = DB::transaction(function () use ($data, $request, $service) {
            $member = Member::create(['membership_number' => MembershipService::reference('MEM')] + $data);
            $service->create($member, $member->level, 'application', $request->user()->id);

            return $member;
        });

        return redirect()->route('members.show', $member)->with('success', 'Member registered. A pending application has been created.');
    }

    public function show(Member $member)
    {
        $member->load('category', 'level');
        $transactions = $member->transactions()->with('level', 'payments')->latest('id')->paginate(10);

        return view('membership.members.show', compact('member', 'transactions'));
    }

    public function edit(Member $member)
    {
        return view('membership.members.form', compact('member') + $this->choices());
    }

    public function update(Request $request, Member $member)
    {
        $member->update($this->validated($request, $member));

        return redirect()->route('members.show', $member)->with('success', 'Member details updated.');
    }

    public function destroy(Member $member)
    {
        DB::transaction(function () use ($member) {
            $member = Member::lockForUpdate()->findOrFail($member->id);
            $member->update(['is_active' => false]);
        });

        return back()->with('success', 'Member deactivated. Transaction and payment history is preserved.');
    }

    public function reactivate(Member $member)
    {
        $member->update(['is_active' => true]);

        return back()->with('success','Member reactivated.');
    }
}
