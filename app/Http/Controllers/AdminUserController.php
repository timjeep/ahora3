<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\Client;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\Country;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    use PasswordValidationRules;

    public function userData(Request $request)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::userData invalid role='.$user->super_role);
            abort(401);
        }

        $total = User::withTrashed()->count();
        $query = User::withTrashed(); // ->with('company_roles');

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%');
        }
        $this->orderBy($query, $request);

        $filtered = (clone $query)->count();
        $users = $query->get();
        $users->load('companies', 'clients', 'contractors', 'roleAssignments.role', 'roleAssignments.roleable');

        return response()->json([
            'data' => $users,
            'pagination' => $this->paginate($total, $filtered, (int) $request->get('page', 1), (int) $request->get('per_page', 20)),
        ]);
    }

    public function userList(Request $request)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::userList invalid role='.$user->super_role);
            abort(401);
        }

        return Inertia::render('admin/user/List', []);
    }

    public function userNew(Request $request)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminController::userAdd invalid role='.$user->super_role);
            abort(401);
        }

        // Get all companies for selection
        $allCompanies = Company::all();
        foreach ($allCompanies as $company) {
            $company->availableRoles = $company->getRoles();
        }

        $allClients = Client::all();
        foreach ($allClients as $client) {
            $client->availableRoles = $client->getRoles();
        }

        $allContractors = Contractor::all();
        foreach ($allContractors as $contractor) {
            $contractor->availableRoles = $contractor->getRoles();
        }

        // Get super admin roles
        $superRoles = User::superRoles();

        $providers = ['google', 'facebook', 'apple', 'microsoft'];
        $provider_logins = ['email' => ($user->password) ? true : false];
        foreach ($providers as $provider) {
            $provider_logins[$provider] = ! is_null($user->{$provider.'_id'});
        }

        return Inertia::render('admin/user/Edit', [
            'user' => new User,
            'provider_logins' => $provider_logins,
            'allCompanies' => $allCompanies,
            'allClients' => $allClients,
            'allContractors' => $allContractors,
            'superRoles' => $superRoles,
            'countries' => Country::countrySelect(),
            'units' => User::$unitSelect,
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function userEdit(Request $request, $user_id)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::userEdit invalid role='.$user->super_role);
            abort(401);
        }

        $user = User::withTrashed()->with('companies', 'clients', 'contractors')->find($user_id);
        if (! $user) {
            abort(404);
        }

        // Get all companies for selection
        $allCompanies = Company::all();
        foreach ($allCompanies as $company) {
            $company->availableRoles = $company->getRoles();
        }

        $allClients = Client::all();
        foreach ($allClients as $client) {
            $client->availableRoles = $client->getRoles();
        }

        $allContractors = Contractor::all();
        foreach ($allContractors as $contractor) {
            $contractor->availableRoles = $contractor->getRoles();
        }

        // Get super admin roles
        $superRoles = User::superRoles();

        $providers = ['google', 'facebook', 'apple', 'microsoft'];
        $provider_logins = ['email' => ($user->password) ? true : false];
        foreach ($providers as $provider) {
            $provider_logins[$provider] = ! is_null($user->{$provider.'_id'});
        }

        return Inertia::render('admin/user/Edit', [
            'user' => $user,
            'provider_logins' => $provider_logins,
            'has_mfa' => (bool) $user->two_factor_secret,
            'allCompanies' => $allCompanies,
            'allClients' => $allClients,
            'allContractors' => $allContractors,
            'superRoles' => $superRoles,
            'units' => User::$unitSelect,
            'timezones' => timezone_identifiers_list(),
        ]);
    }

    public function userUpdate(Request $request, $user_id)
    {
        $user = User::find($user_id);
        if (! $user) {
            abort(401);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'country_code' => [],
            'units' => [],
            'timezone' => [],
            'super_role' => ['nullable', 'in:'.implode(',', array_keys(User::superRoles()))],
            'companies' => ['array'],
            'clients' => ['array'],
            'contractors' => ['array'],
            'password' => $this->passwordRules(false),
        ]);

        // Update basic user information
        $user->name = $request->name;
        $user->email = $request->email;
        $user->country_code = $request->country_code;
        $user->units = $request->units;
        $user->timezone = $request->timezone;
        $user->super_role = $request->super_role ?? User::SUPERNONE;
        if ($request->disabled) {
            if (! $user->disabled_at) {
                $user->disabled_at = now();
            }
        } else {
            $user->disabled_at = null;
        }
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // Update company associations
        if ($request->has('companies') && is_array($request->companies)) {
            Log::info('AdminUserController::userUpdate companies', ['companies' => $request->companies]);
            $companyData = [];
            foreach ($request->companies as $company_data) {
                // Validate each company entry has required fields
                if (isset($company_data['company_id']) && isset($company_data['role'])) {
                    // Verify company exists and role is valid
                    $company = Company::find($company_data['company_id']);
                    if ($company && $company->hasRole($company_data['role'])) {
                        $companyData[$company_data['company_id']] = ['role_id' => $company_data['role']];
                    } else {
                        Log::warning('Invalid company data in user update', [
                            'user_id' => $user->id,
                            'company_data' => $company,
                        ]);
                    }
                }
            }
            $user->companies()->sync($companyData);
        }

        // Update company associations
        if ($request->has('clients') && is_array($request->clients)) {
            Log::info('AdminUserController::userUpdate clients', ['clients' => $request->clients]);
            $clientData = [];
            foreach ($request->clients as $client_data) {
                // Validate each client entry has required fields
                if (isset($client_data['client_id']) && isset($client_data['role'])) {
                    // Verify client exists and role is valid
                    $client = Client::find($client_data['client_id']);
                    if ($client && $client->hasRole($client_data['role'])) {
                        $clientData[$client_data['client_id']] = ['role_id' => $client_data['role']];
                    } else {
                        Log::warning('Invalid client data in user update', [
                            'user_id' => $user->id,
                            'client_data' => $client,
                        ]);
                    }
                }
            }
            $user->clients()->sync($clientData);
        }

        // Update company associations
        if ($request->has('contractors') && is_array($request->contractors)) {
            Log::info('AdminUserController::userUpdate contractors', ['contractors' => $request->contractors]);
            $contractorData = [];
            foreach ($request->contractors as $contractor_data) {
                // Validate each contractor entry has required fields
                if (isset($contractor_data['contractor_id']) && isset($contractor_data['role'])) {
                    // Verify contractor exists and role is valid
                    $contractor = Contractor::find($contractor_data['contractor_id']);
                    if ($contractor && $contractor->hasRole($contractor_data['role'])) {
                        $contractorData[$contractor_data['contractor_id']] = ['role_id' => $contractor_data['role']];
                    } else {
                        Log::warning('Invalid contractor data in user update', [
                            'user_id' => $user->id,
                            'contractor_data' => $contractor,
                        ]);
                    }
                }
            }
            $user->contractors()->sync($contractorData);
        }

        return redirect(route('admin.user.list'));
    }

    public function userCreate(Request $request)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::userCreate invalid role='.$user->super_role);
            abort(401);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
            'country_code' => [],
            'units' => [],
            'timezone' => [],
            'super_role' => ['nullable', 'in:'.implode(',', array_keys(User::superRoles()))],
            'companies' => ['array'],
            'companies.*.company_id' => ['required', 'exists:companies,id'],
            'companies.*.role' => ['required'], // , 'in:admin,user,viewer'],
            'contractors' => ['array'],
            'contractors.*.contractor_id' => ['required', 'exists:contractors,id'],
            'contractors.*.role' => ['required'], // , 'in:admin,user,viewer'],
            'clients' => ['array'],
            'clients.*.client_id' => ['required', 'exists:clients,id'],
            'clients.*.role' => ['required'], // , 'in:admin,user,viewer'],
        ]);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'country_code' => $request->country_code,
            'units' => $request->units,
            'timezone' => $request->timezone,
            'super_role' => $request->super_role ?? User::SUPERNONE,
            'email_verified_at' => now(),
        ]);

        // Add company associations
        if ($request->has('companies')) {
            $companyData = [];
            foreach ($request->companies as $company) {
                $companyData[$company['company_id']] = ['role_id' => $company['role']];
            }
            $user->companies()->sync($companyData);
        }
        if ($request->has('contractors')) {
            $contractorData = [];
            foreach ($request->contractors as $contractor) {
                $contractorData[$contractor['contractor_id']] = ['role_id' => $contractor['role']];
            }
            $user->contractors()->sync($contractorData);
        }

        if ($request->has('clients')) {
            $clientData = [];
            foreach ($request->clients as $client) {
                $clientData[$client['client_id']] = ['role_id' => $client['role']];
            }
            $user->clients()->sync($clientData);
        }

        return redirect(route('admin.user.list'));
    }

    public function userDelete(Request $request, $user_id)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::userDelete invalid role='.$user->super_role);
            abort(401);
        }

        $user = User::find($user_id);
        if (! $user) {
            abort(404);
        }

        $user->companies()->detach();
        $user->delete();

        return redirect(route('admin.user.list'));
    }

    public function userRestore(Request $request, $user_id)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::userRestore invalid role='.$user->super_role);
            abort(401);
        }

        $user = User::withTrashed()->find($user_id);
        if (! $user) {
            abort(404);
        }

        $user->restore();

        return redirect(route('admin.user.list'));
    }

    public function userForceDelete(Request $request, $user_id)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::userForceDelete invalid role='.$user->super_role);
            abort(401);
        }

        $user = User::withTrashed()->find($user_id);
        if (! $user) {
            abort(404);
        }

        $user->forceDelete();

        return redirect(route('admin.user.list'));
    }

    public function sendResetPasswordEmail(Request $request, $user_id)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::sendResetPasswordEmail invalid role='.$user->super_role);
            abort(401);
        }

        $targetUser = User::find($user_id);
        if (! $targetUser) {
            return back()->with('error', 'User not found');
        }

        // Send password reset notification
        $targetUser->sendPasswordResetNotification(
            app('auth.password.broker')->createToken($targetUser)
        );

        return back()->with('success', 'Password reset email sent successfully to '.$targetUser->email);
    }

    public function roleData(Request $request)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::roleData invalid role='.$user->super_role);
            abort(401);
        }

        $total = Role::all()->count();
        $query = Role::query()->with('company');
        $this->orderBy($query, $request);

        $filtered = (clone $query)->count();

        return response()->json([
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $query->get(),
        ]);
    }

    public function roleList(Request $request)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::roleList invalid role='.$user->super_role);
            abort(401);
        }

        return Inertia::render('admin/role/List', []);
    }

    public function roleNew(Request $request)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::roleAdd invalid role='.$user->super_role);
            abort(401);
        }

        return Inertia::render('admin/role/Edit', [
            'role' => null,
            'superRoles' => User::superRoles(),
            'permissionStrings' => Role::PERMISSION_STRINGS,
            'availablePermissions' => Role::availablePermissions(),
        ]);
    }

    public function roleEdit(Request $request, $role_id)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::roleEdit invalid role='.$user->super_role);
            abort(401);
        }

        $role = Role::with('company')->find($role_id);
        if (! $role) {
            abort(404);
        }

        return Inertia::render('admin/role/Edit', [
            'role' => $role,
            'superRoles' => User::superRoles(),
            'permissionStrings' => Role::PERMISSION_STRINGS,
            'availablePermissions' => Role::availablePermissions(),
        ]);
    }

    public function roleCreate(Request $request)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::roleCreate invalid role='.$user->super_role);
            abort(401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $role = new Role;
        $role->name = $request->name;
        $role->slug = $request->slug;
        $role->permissions = $request->permissions ?? [];
        $role->company_id = $request->company_id;
        $role->save();

        return redirect(route('admin.role.list'));
    }

    public function roleUpdate(Request $request, $role_id)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::roleUpdate invalid role='.$user->super_role);
            abort(401);
        }

        $role = Role::find($role_id);
        if (! $role) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug,'.$role_id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $role->name = $request->name;
        $role->slug = $request->slug;
        $role->permissions = $request->permissions ?? [];
        $role->company_id = $request->company_id;
        $role->save();

        return redirect(route('admin.role.list'));
    }

    public function roleDelete(Request $request, $role_id)
    {
        $user = Auth::user();
        if (! $user->isSuperAdmin()) {
            Log::error('AdminUserController::roleDelete invalid role='.$user->super_role);
            abort(401);
        }

        $role = Role::find($role_id);
        if (! $role) {
            abort(404);
        }

        $role->delete();

        return redirect(route('admin.role.list'));
    }
}
