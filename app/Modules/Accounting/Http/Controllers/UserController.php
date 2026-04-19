<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Accounting\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogService $log) {}

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        Gate::authorize('admin-only');

        $companyId = $request->user()->company_id;

        $users = User::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('accounting.users.index', compact('users'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create(): View
    {
        Gate::authorize('admin-only');

        return view('accounting.users.create');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('admin-only');

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'in:admin,accountant,viewer'],
        ]);

        $user = User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'password'   => $validated['password'],   // cast to 'hashed' by model
            'role'       => $validated['role'],
            'company_id' => $request->user()->company_id,
        ]);

        $this->log->log(
            $request->user()->company_id,
            'created', 'user', $user->id,
            $user->name,
            "أنشأ مستخدماً جديداً ({$user->roleName()})."
        );

        return redirect()
            ->route('accounting.users.index')
            ->with('success', "تم إنشاء المستخدم [{$user->name}] بنجاح.");
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('admin-only');
        $this->authorizeCompany($request, $user);

        return view('accounting.users.edit', compact('user'));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('admin-only');
        $this->authorizeCompany($request, $user);

        $rules = [
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', "unique:users,email,{$user->id}"],
            'role'  => ['required', 'in:admin,accountant,viewer'],
        ];

        // Password is optional on update
        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        $updateData = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role'],
        ];

        if ($request->filled('password')) {
            $updateData['password'] = $validated['password'];
        }

        $user->update($updateData);

        $this->log->log(
            $request->user()->company_id,
            'updated', 'user', $user->id,
            $user->name,
            "عدَّل بيانات المستخدم."
        );

        return redirect()
            ->route('accounting.users.index')
            ->with('success', "تم تحديث بيانات [{$user->name}] بنجاح.");
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('admin-only');
        $this->authorizeCompany($request, $user);

        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'لا يمكنك حذف حسابك الخاص.']);
        }

        $name = $user->name;
        $user->delete();

        $this->log->log(
            $request->user()->company_id,
            'deleted', 'user', $user->id,
            $name,
            "حذف المستخدم [{$name}]."
        );

        return redirect()
            ->route('accounting.users.index')
            ->with('success', "تم حذف المستخدم [{$name}].");
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function authorizeCompany(Request $request, User $user): void
    {
        abort_if($user->company_id !== $request->user()->company_id, 403);
    }
}
