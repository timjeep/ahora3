<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Company extends Model
{
    use SoftDeletes;
    
    public function unitStr()
    {
        return Field::unitStr($this->units);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'url',
    ];

    public static function getName($company_id){
        return Company::where('id', $company_id)->pluck('name')->first();
    }

    public static function findByDomain($domain) {
        return self::where('domain', $domain)->orWhere('domain', 'www.'. $domain)->orWhere('domain2',$domain)->orWhere('domain2','www.'.$domain)->first();
    }

    public static function myCustomers($company_id){
        if($company_id){
            return Customer::where('company_id', $company_id)->get();
        } else {
            return Customer::all();
        }
    }

    public static function myCrew(){
        $users = Company::findOrFail(config('company.id'))->users;
        $crew = [];
        foreach($users as $user){
            if($user->hasRole('Company Crew')){
                $crew[] = $user;
            }
        }
        return $crew;
    }

    public function getFavicon()
    {
        if($this->favicon){
            return $this->favicon->url->image(Media::MEDIASCALE_100);
        } elseif($this->logo){
            return $this->logo->image(Media::MEDIASCALE_100);
        } else {
            return asset('images/logo.png');
        }
    }

    public function taskRate()
    {
        // You must also update the product price in Stripe!!!
        return 9.0; // Changed 24 Jan 2025
    }

    public function baseRate()
    {
        // You must also update the product price in Stripe!!!
        return 32.0; // New 24 Jan 2025
    }

    public static function roles()
    {
        return Role::whereRaw('SUBSTRING(name, 1,  7) = "Company"')->get();
    }

    public static function permissions()
    {
        return Permission::whereRaw('SUBSTRING(name, 1,  7) = "company"')->get();
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function logo()
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }

    public function letterhead_top()
    {
        return $this->belongsTo(Media::class, 'letterhead_top_id');
    }

    public function letterhead_bottom()
    {
        return $this->belongsTo(Media::class, 'letterhead_bottom_id');
    }

    public function favicon(){
        return $this->belongsTo(Media::class, 'favicon_id');
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }

    public function currency(){
        return $this->belongsTo(Currency::class);
    }

    public function countries(){
        return $this->belongsToMany(Country::class);
    }

    public function currencies(){
        return $this->belongsToMany(Currency::class);
    }

    public function billing(){
        return $this->belongsTo(Address::class,'billing_address_id');
    }

    public function shipping(){
        return $this->belongsTo(Address::class,'shipping_address_id');
    }
}
