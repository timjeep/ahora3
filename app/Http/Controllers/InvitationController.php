<?php

namespace App\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Models\Client;
use App\Models\Company;
use App\Models\Contractor;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InvitationController extends Controller
{
    use PasswordValidationRules;

    /**
     * Show the invitation form
     */
    public function adminInvitationNew()
    {
        $user = Auth::user();

        return Inertia::render('admin/user/Invite', [
            'roles' => Role::all(),
            'superRoles' => User::superRolesArray(),
            'canCreateSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    /**
     * Send an invitation
     */
    public function adminInvitationCreate(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'invitable_type' => 'required|in:company,client,contractor',
            'company_id' => 'nullable|exists:companies,id',
            'client_id' => 'nullable|exists:clients,id',
            'contractor_id' => 'nullable|exists:contractors,id',
            'super_role' => 'nullable|in:'.implode(',', array_keys(User::superRoles())),
        ]);

        $invitable_type = $request->invitable_type;
        switch ($invitable_type) {
            case 'company':
                $invitable_type = Company::class;
                $invitable_id = $request->company_id;
                break;
            case 'client':
                $invitable_type = Client::class;
                $invitable_id = $request->client_id;
                break;
            case 'contractor':
                $invitable_type = Contractor::class;
                $invitable_id = $request->contractor_id;
                break;
        }

        // Add custom validation logic
        $validator->after(function ($validator) use ($request, $invitable_type, $invitable_id) {

            // Check if user already exists
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                // Check if user is already in the company
                switch ($invitable_type) {
                    case Company::class:
                        if ($existingUser->belongsToCompany($invitable_id)) {
                            $validator->errors()->add('email', 'This user is already a member of the selected company.');
                        }
                        break;
                    case Client::class:
                        if ($existingUser->belongsToClient($invitable_id)) {
                            $validator->errors()->add('email', 'This user is already a member of the selected client.');
                        }
                        break;
                    case Contractor::class:
                        if ($existingUser->belongsToContractor($invitable_id)) {
                            $validator->errors()->add('email', 'This user is already a member of the selected contractor.');
                        }
                        break;
                }
            }

            // Check if there's already a pending invitation
            $existingInvitation = Invitation::where('email', $request->email)
                ->where('invitable_type', $invitable_type)
                ->where('invitable_id', $invitable_id)
                ->where('accepted_at', null)
                ->where('expires_at', '>', now())
                ->first();

            if ($existingInvitation) {
                $validator->errors()->add('email', 'There is already a pending invitation for this email address.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Create the invitation
        $invitation = Invitation::create([
            'invitable_type' => $invitable_type,
            'invitable_id' => $invitable_id,
            'invited_by' => $user->id,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'super_role' => $request->super_role ?? User::SUPERNONE,
        ]);

        // Send invitation email
        $this->sendInvitationEmail($invitation);

        return redirect()->route('admin.user.list')->with('success', 'Invitation sent successfully!');

    }

    public function invitationNew(Request $request)
    {
        $user = Auth::user();
        $model = $request->user()->currentRoleable;
        if (! $model) {
            abort(404);
        }

        $roles = $model->getRoles();

        return Inertia::render('user/Invite', [
            'name' => $model->name,
            'roles' => $roles,
        ]);
    }

    /**
     * Send an invitation
     */
    public function invitationCreate(Request $request)
    {
        $request->user()->checkPermission('user.invite');
        $model = $request->user()->currentRoleable;
        if (! $model) {
            abort(404);
        }
        $user = Auth::user();

        $roles = array_keys($model->getRoles());

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'role_id' => ['required', Rule::in($roles)],
        ]);

        // Add custom validation logic
        $validator->after(function ($validator) use ($request, $model) {
            // Check if user already exists
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                // Check if user is already in the organization
                if ($existingUser->roleAssignments()->where('roleable_type', get_class($model))->where('roleable_id', $model->id)->exists()) {
                    $validator->errors()->add('email', 'This user is already a member of this organization.');
                }
            }

            // Check if there's already a pending invitation
            $existingInvitation = Invitation::where('email', $request->email)
                ->where('invitable_type', get_class($model))
                ->where('invitable_id', $model->id)
                ->where('accepted_at', null)
                ->where('expires_at', '>', now())
                ->first();

            if ($existingInvitation) {
                $validator->errors()->add('email', 'There is already a pending invitation for this email address.');
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Create the invitation
        $invitation = Invitation::create([
            'invitable_type' => get_class($model),
            'invitable_id' => $model->id,
            'invited_by' => $user->id,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'super_role' => User::SUPERNONE,
        ]);

        // Send invitation email
        $this->sendInvitationEmail($invitation);

        return redirect()->route('user.list')->with('success', 'Invitation sent successfully!');
    }

    /**
     * Show the invitation acceptance form
     */
    public function show(string $token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isValid()) {
            return Inertia::render('auth/InvitationExpired');
        }

        return Inertia::render('auth/AcceptInvitation', [
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'invitable' => $invitation->invitable?->name,
                'role' => $invitation->role?->name,
                'super_role' => $invitation->super_role,
                'inviter' => $invitation->inviter->name,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Accept the invitation and register the user
     */
    public function accept(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (! $invitation || ! $invitation->isValid()) {
            return back()->withErrors(['invitation' => 'Invalid or expired invitation.']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'password' => $this->passwordRules(),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if user already exists
        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            // User exists, check if they're already a member of this organization
            $existingMembership = $existingUser->roleAssignments()
                ->where('roleable_id', $invitation->invitable_id)
                ->where('roleable_type', $invitation->invitable_type)
                ->first();

            if ($existingMembership) {
                // User is already a member, update their role
                $existingMembership->update([
                    'role_id' => $invitation->role_id,
                ]);
            } else {
                // User is not a member, add them to the organization
                $existingUser->roleAssignments()->create([
                    'role_id' => $invitation->role_id,
                    'roleable_type' => $invitation->invitable_type,
                    'roleable_id' => $invitation->invitable_id,
                ]);
            }

            if ($invitation->super_role) {
                $existingUser->update(['super_role' => User::SUPERADMIN]);
            }

            $user = $existingUser;
        } else {
            // Create new user
            $user = User::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => bcrypt($request->password),
                'email_verified_at' => now(),
                'super_role' => $invitation->super_role,
            ]);

            // Add user to organization
            $user->roleAssignments()->create([
                'role_id' => $invitation->role_id,
                'roleable_type' => $invitation->invitable_type,
                'roleable_id' => $invitation->invitable_id,
            ]);
        }

        // Mark invitation as accepted
        $invitation->markAsAccepted();

        $user->current_roleable_type = $invitation->invitable_type;
        $user->current_roleable_id = $invitation->invitable_id;
        $user->save();

        // Log the user in
        Auth::login($user);

        session()->put('current_roleable_type', $invitation->invitable_type);
        session()->put('current_roleable_id', $invitation->invitable_id);
        config(['current_roleable_type' => $invitation->invitable_type, 'current_roleable_id' => $invitation->invitable_id]);

        return redirect()->route('home')->with('success', 'Welcome! You have successfully joined the organization.');
    }

    /**
     * Send invitation email
     */
    private function sendInvitationEmail(Invitation $invitation)
    {
        $invitationUrl = route('invitation.show', $invitation->token);

        // Eager load relationships needed in the email view
        $invitation->load(['inviter', 'invitable', 'role']);

        Mail::send('emails.invitation', [
            'invitation' => $invitation,
            'invitationUrl' => $invitationUrl,
        ], function ($message) use ($invitation) {
            $message->to($invitation->email)
                ->subject('You\'re invited to join '.$invitation->invitable->name.' on '.config('app.name'));
        });
    }
}
