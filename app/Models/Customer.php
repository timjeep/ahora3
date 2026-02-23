<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Site;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Customer extends Model
{
    use SoftDeletes;
    
    public function unitStr()
    {
        return Field::unitStr($this->units);
    }

    public static function getName($customer_id){
        return Customer::where('id', $customer_id)->pluck('name')->first();
    }

    public static function findBySlug($slug) {
        $company = self::where('slug', $slug)->first();
        return $company;
    }

    public static function roles()
    {
        return Role::whereRaw('SUBSTRING(name, 1,  6) = "Client"')->get();
    }

    public static function permissions()
    {
        return Permission::whereRaw('SUBSTRING(name, 1,  6) = "client"')->get();
    }

    public static function myCustomers(){
        return Customer::where('company_id', config('company.id'))->get();
    }

    public static function customerList()
    {
        $list = [''=>'Any'];
        foreach(Customer::myCustomers() as $customer){
            $list[$customer->id] = $customer->name;
        }
        return $list;
    }

    public function numSites()
    {
        return CustomerSite::where('customer_id', $this->id)->count();
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function logo()
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }

    public function sites()
    {
        return $this->belongsToMany(Site::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    
    public function countries(){
        return $this->belongsToMany(Country::class);
    }

    public function billing()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function shipping()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }


    public function apiCustomerInfo($brief=false)
    {
        if($brief){
            return ['id'=>$this->id, 'name'=>$this->name];
        } else {
            if($this->logo){
                $logo = $this->logo->image(Media::MEDIASCALE_100,'png',false);
            } else {
                $logo = null;
            }
    
            $users = [];
            foreach($this->users as $user){
                $users[] = $user->apiUserInfo('Client');
            }
            return ['id'=>$this->id, 'name'=>$this->name,'slug'=>$this->slug,'logo'=>$logo, 'email'=>$this->email, 'telephone'=>$this->telephone, 'users'=>$users];
        }
    }
}
