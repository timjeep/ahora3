<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use HasPermissions;
    use SoftDeletes;

    public static $companyRoles = [
        'Admin', 'Manager', 'Consultant', 'Crew',
    ];

    public static $companyPermissions = [
        'analytic',
        'antenna',

        'client',
        'currency',
        'dashboard',
        'info',
        'job',
        'microwave',
        'quote',
        'site',
        'task',
        'user',
        'vehicle',
        'work',
        'notification',
    ];

    public static $readwrite = ['read','write'];

    public static $clientRoles = ['Client'];

    public static $clientPermissions = [
        'info',
        'dashboard',
        'user',
        'site',
        'report',
        'job',
        'quote',
        'notification',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function firstLast(){
        // dummy, leave it
    }

    public static function myUsers(){
        return User::whereRelation('companies','company_id',config('company.id'))->get();
    }

    public function getCustomerRoleNames(){
        $names = [];
        foreach($this->getRoleNames()->toArray() as $name){
            if(!strncasecmp($name, 'Client ',7)){
                $names[] = substr($name,7);
            }
        }
        return $names;
    }

    public function getCompanyRoleNames(){
        $names = [];
        foreach($this->getRoleNames()->toArray() as $name){
            if(!strncasecmp($name, 'Company ',8)){
                $names[] = substr($name,8);
            }
        }
        return $names;
    }

    public function companyNames()
    {
        $names = [];
        foreach($this->companies as $company){
            $names[] = $company->name;
        }
        return $names;
    }

    public function customerNames()
    {
        $names = [];
        foreach($this->customers as $customer){
            $names[] = $customer->name;
        }
        return $names;
    }

    public function apiRoles($prefix)
    {
        $roles = [];
        foreach($this->roles as $role)
        {
            $name = $role->name;
            foreach(['Company','Client'] as $prefix){
                if(strncasecmp($prefix,$name,strlen($prefix))==0){
                    $roles[]=Str::slug(substr($name,strlen($prefix)+1));
                }
            }
        }
        return $roles;
    }

    public function isSubscribed($notification_type)
    {
        return (UserSubscribed::where('user_id',$this->id)->where('notification_type',$notification_type)->first() != null);
    }

    public static function subscribed($notification_type)
    {
        return UserSubscribed::where('notification_type',$notification_type)->pluck('user_id')->toArray();
    }

    public function syncSubscriptions($notification_types)
    {
        UserSubscribed::syncSubscriptions($this->id,$notification_types);
    }

    public function subscriptions()
    {
        return UserSubscribed::where('user_id',$this->id)->pluck('notification_type')->toArray();
    }

    public function apiPermissions($prefix)
    {
        $permissions = [];
        foreach($this->getAllPermissions() as $permission)
        {
            foreach(['Company','Client'] as $prefix){
                $name = $permission->name;
                if(strncasecmp($prefix,$name,strlen($prefix))==0){
                    $permissions[]=Str::slug(substr($name,strlen($prefix)+1));
                }
            }
        }
        return $permissions;
    }

    public function apiSubscriptions()
    {
        $subscriptions = [];
        foreach($this->subscriptions() as $notification_type){
            $subscriptions[$notification_type] = Notification::$notificationStrings[$notification_type];
        }
        return $subscriptions;
    }

    public function isCompanyEmployee($company_id=null)
    {
        if(!$company_id){
            $company_id = config('company.id');
        }
        return in_array($company_id, $this->companies->pluck('id')->toArray());
    }

    public function isCustomerEmployee($customer_id=null)
    {
        if(!$customer_id){
            $customer_id = config('customer.id');
        }
        return in_array($customer_id, $this->customers->pluck('id')->toArray());
    }

    public function notifications()
    {
        return $this->belongsToMany(Notification::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class);
    }

    public function customers()
    {
        return $this->belongsToMany(Customer::class);
    }

    public function jobs()
    {
        return $this->belongsToMany(Job::class);
    }

    public function apiUserInfo($prefix='Company',$brief=false)
    {
        if($brief){
            return ['id'=>$this->id, 'email'=>$this->email, 'name'=>$this->name, 'you'=>($this->id == Auth::id())];
        } else {
            $customers = [];
            foreach($this->customers as $customer){
                $customers[] = $customer->apiCustomerInfo(true);
            }
            return ['id'=>$this->id, 'email'=>$this->email,'name'=>$this->name,'you'=>($this->id == Auth::id()),'roles'=>$this->apiRoles($prefix),'permissions'=>$this->apiPermissions($prefix),'subscriptions'=>$this->apiSubscriptions(),'customers'=>$customers];
        }
    }
}
