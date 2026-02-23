<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UiModeController extends Controller
{
    private function dashboard(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return redirect()->route('admin.dashboard');
        } else {
            $mode = $request->session()->get('ui.mode');
            if ($mode) {
                return redirect()->route($mode.'.dashboard');
            } else {
                if ($user->current_roleable_type && $user->current_roleable_id) {
                    $mode = strtolower(str_replace('App\Models\\', '', $user->current_roleable_type));
                    $request->session()->put('ui.mode', $mode);
                    $request->session()->put('current_roleable_type', $user->current_roleable_type);
                    $request->session()->put('current_roleable_id', $user->current_roleable_id);

                    return redirect()->route($mode.'.dashboard');
                } else {
                    $assignedRole = $user->roleAssignments()->with('roleable')->first();
                    if ($assignedRole) {
                        $mode = strtolower(str_replace('App\Models\\', '', $assignedRole->roleable_type));
                        $request->session()->put('ui.mode', $mode);
                        $request->session()->put('current_roleable_type', $assignedRole->roleable_type);
                        $request->session()->put('current_roleable_id', $assignedRole->roleable_id);

                        return redirect()->route($mode.'.dashboard');
                    } else {
                        return redirect()->route('welcome');
                    }
                }
            }
        }
    }

    public function setDashboard(Request $request)
    {
        // Someone trying to go to dashboard without any prefix (admin/company/client)
        return $this->dashboard($request);
    }

    public function setUiMode(Request $request)
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:company,client,admin'],
            'roleableType' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value !== null) {
                        $allowedTypes = [
                            'company',
                            'client',
                            Company::class,
                            Client::class,
                        ];
                        if (! in_array($value, $allowedTypes, true)) {
                            $fail('The '.$attribute.' must be one of: company, client, or a valid model class name.');
                        }
                    }
                },
            ],
            'roleableId' => ['nullable', 'string'],
        ]);

        $mode = $validated['mode'];
        $user = $request->user();
        $roleableType = isset($validated['roleableType']) ? $validated['roleableType'] : null;
        $roleableId = isset($validated['roleableId']) ? $validated['roleableId'] : null;

        // Only super admins can enter "admin" mode.
        if ($mode === 'admin' && ! $user?->isSuperAdmin()) {
            return $this->dashboard($request, $mode);
        }

        $request->session()->put('ui.mode', $mode);
        $request->session()->put('current_roleable_type', $roleableType);
        $request->session()->put('current_roleable_id', $roleableId);

        $user = $request->user();
        $user->current_roleable_type = $roleableType;
        $user->current_roleable_id = $roleableId;
        $user->timestamps = false;
        $user->save();

        return redirect()->route($mode.'.dashboard');
    }

    public function unitsUpdate(Request $request): RedirectResponse
    {
        $request->user()->units = $request->units;
        $request->user()->save();

        return to_route('appearance.edit');
    }

    public function languageUpdate(Request $request): RedirectResponse
    {
        $request->user()->locale = $request->locale;
        $request->user()->save();

        return to_route('appearance.edit');
    }

    public function cookiesUpdate(Request $request): RedirectResponse
    {
        $request->user()->cookies = $request->boolean('cookies');
        $request->user()->save();

        return to_route('appearance.edit');
    }
}
