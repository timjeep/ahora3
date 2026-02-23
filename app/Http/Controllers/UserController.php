<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function userData(Request $request): JsonResponse
    {
        // $request->user()->checkPermission('user.view');
        $user = $request->user();
        $model = $user->currentRoleable;
        if (! $model) {
            abort(404);
        }

        $query = $model->users();

        // Apply filters
        if ($request->has('name') && $request->name) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        // Get total count after filters
        $total = $query->count();
        $sql = $query->toSql();

        // Apply ordering
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        $sortFieldMap = [
            'name' => 'name',
            'email' => 'email',
            'created_at' => 'created_at',
            'lastlogin_at' => 'lastlogin_at',
        ];

        $sortField = $sortFieldMap[$sortBy] ?? 'name';
        $query->orderBy($sortField, $sortOrder);

        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $users = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
        foreach ($users as $user) {
            $user->roles = $user->myRoles()->where('roleable_type', get_class($model))->where('roleable_id', $model->id)->get();
        }

        return response()->json([
            'data' => $users,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
            ],
        ]);
    }

    public function userList(Request $request)
    {
        $request->user()->checkPermission('user.view');
        $model = $request->user()->currentRoleable;

        return Inertia::render('user/List');
    }

    public function userNew(Request $request)
    {
        $request->user()->checkPermission('user.edit');
        $model = $request->user()->currentRoleable;

        $user = new User;
        $roles = $model->getRoles();

        return Inertia::render('user/Edit', ['user' => $user, 'countries' => Country::countrySelect(), 'timezones' => timezone_identifiers_list(), 'units' => User::$unitSelect, 'roles' => $roles]);
    }

    public function userEdit(Request $request, $user_id)
    {
        logger()->info('auth snapshot', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'expects_json' => $request->expectsJson(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'auth_check' => auth()->check(),
            'user_id' => optional($request->user())->id,
        ]);

        $request->user()->checkPermission('user.edit');
        $model = $request->user()->currentRoleable;

        $user = User::find($user_id);
        if (! $user) {
            abort(404);
        }

        $user->role_id = $user->roleAssignments()->where('roleable_type', get_class($model))->where('roleable_id', $model->id)->first()?->role_id;
        $providers = ['google', 'facebook', 'apple', 'microsoft'];
        $provider_logins = ['email' => ($user->password) ? true : false];
        foreach ($providers as $provider) {
            $provider_logins[$provider] = ! is_null($user->{$provider.'_id'});
        }
        $has_mfa = $user->two_factor_secret ? true : false;

        $roles = $model->getRoles();

        return Inertia::render('user/Edit', ['user' => $user, 'provider_logins' => $provider_logins, 'has_mfa' => $has_mfa, 'countries' => Country::countrySelect(), 'timezones' => timezone_identifiers_list(), 'units' => User::$unitSelect, 'roles' => $roles]);
    }

    public function userCreate(Request $request)
    {
        $request->user()->checkPermission('user.edit');
        $model = $request->user()->currentRoleable;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'country_code' => ['required', 'string', 'size:2'],
            'timezone' => ['required', 'string', 'max:100'],
            'units' => ['required', 'string', 'max:6'],
            'password' => $this->passwordRules(false),
            'role_id' => ['nullable', 'string', 'max:255'],
        ]);
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->country_code = $request->country_code;
        $user->timezone = $request->timezone;
        $user->units = $request->units;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->current_roleable_type = get_class($model);
        $user->current_roleable_id = $model->id;
        $user->save();

        $user->roleAssignments()->create([
            'roleable_type' => get_class($model),
            'roleable_id' => $model->id,
            'role_id' => $request->role_id,
        ]);

        return redirect()->route('user.list')->with('success', 'User created successfully');
    }

    public function userUpdate(Request $request, $user_id)
    {
        $request->user()->checkPermission('user.edit');
        $user = User::find($user_id);
        if (! $user) {
            abort(404);
        }
        $model = $request->user()->currentRoleable;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'country_code' => ['required', 'string', 'size:2'],
            'timezone' => ['required', 'string', 'max:100'],
            'units' => ['required', 'string', 'max:6'],
            'password' => $this->passwordRules(false),
        ]);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->country_code = $request->country_code;
        $user->timezone = $request->timezone;
        $user->units = $request->units;
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();
        $user->roleAssignments()->where('roleable_type', get_class($model))->where('roleable_id', $model->id)->update(['role_id' => $request->role_id]);

        return redirect()->route('user.list')->with('success', 'User updated successfully');
    }

    public function userDelete(Request $request)
    {
        $request->user()->checkPermission('user.edit');
        $user = User::find($request->id);
        $model = $request->user()->currentRoleable;

        if (! $user) {
            abort(404);
        }
        $user->roleAssignments()->where('roleable_type', get_class($model))->where('roleable_id', $model->id)->delete();
        $user->delete();

        return Inertia::render('user/List');
    }

    public function sendResetPasswordEmail(Request $request, $user_id)
    {
        $request->user()->checkPermission('user.edit');
        $user = User::findOrFail($user_id);

        // Send password reset notification to the authenticated user
        $user->sendPasswordResetNotification(
            app('auth.password.broker')->createToken($user)
        );

        return back()->with('success', 'Password reset email sent successfully to '.$user->email);
    }
}
