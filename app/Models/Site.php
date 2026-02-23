<?php

namespace App\Models;

use App\Http\Livewire\CustomerSiteTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Malhal\Geographical\Geographical;
use App\Models\Antenna;
use Exception;

class Site extends Model
{
    use SoftDeletes, Geographical;

    protected static $kilometers = true;
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
    ];

    public static function exists(int $company_id, int $country_id, $island, $identifier)
    {
        $site = Site::where('company_id',$company_id)->where('country_id',$country_id)->where('island',$island)->where('identifier',$identifier)->first();
        if ($site){
            return $site->id;
        } else {
            return false;
        }
    }

    public static function siteList()
    {
        $list = [''=>'Any'];
        foreach(Site::mySites() as $site){
            $list[$site->id] = $site->name;
        }
        return $list;
    }

    public static function identifierList()
    {
        $list = [''=>'Any'];
        foreach(Site::mySites() as $site){
            $list[$site->id] = $site->identifier;
        }
        return $list;
    }

/*
    public static function customerSiteList($customer_id)
    {
        $list = [''=>'Any'];
        foreach(Site::myCustomerSites($customer_id) as $site){
            $list[$site->id] = $site->name;
        }
        return $list;
    }*/

    public function myActiveJobSite()
    {
        $sites = Job::myActiveJobSiteList();
        return in_array($this->id,$sites);
    }

    public static function mySites(){
        return Site::where('company_id', config('company.id'))->get();
    }

    public static function myCompanySites($customer_id)
    {
        return Site::where('sites.company_id', config('company.id'))->join('customer_site', 'customer_site.site_id', '=', 'sites.id')->where('customer_site.customer_id', $customer_id)->orderBy('identifier','asc')->get();
    }

    public static function myCustomerSites($customer_id, $country_id, $island)
    {
        return Site::where('sites.company_id', config('company.id'))->join('customer_site', 'customer_site.site_id', '=', 'sites.id')->where('customer_site.customer_id', $customer_id)->where('sites.country_id',$country_id)->where('sites.island',$island)->select('sites.*')->orderBy('identifier','asc')->get();
    }

    public static function myCustomerIslands($customer_id, $country_id)
    {
        return Site::where('sites.company_id', config('company.id'))->join('customer_site', 'customer_site.site_id', '=', 'sites.id')->where('customer_site.customer_id', $customer_id)->where('sites.country_id',$country_id)->select('island')->distinct()->get();
    }

    public static function customerSites($customer_id, $country_id=null, $island=null)
    {
        if($island){
            if($country_id){
                return Site::where('sites.company_id', config('company.id'))->join('customer_site', 'customer_site.site_id', '=', 'sites.id')->where('customer_site.customer_id', $customer_id)->where('sites.country_id',$country_id)->where('sites.island',$island)->select('sites.*')->orderBy('identifier','asc')->get();
            } else {
                return Site::where('sites.company_id', config('company.id'))->join('customer_site', 'customer_site.site_id', '=', 'sites.id')->where('customer_site.customer_id', $customer_id)->where('sites.island',$island)->select('sites.*')->orderBy('identifier','asc')->get();
            }
        } else {
            if($country_id){
                return Site::where('sites.company_id', config('company.id'))->join('customer_site', 'customer_site.site_id', '=', 'sites.id')->where('customer_site.customer_id', $customer_id)->where('sites.country_id',$country_id)->select('sites.*')->orderBy('identifier','asc')->get();
            } else {
                return Site::where('sites.company_id', config('company.id'))->join('customer_site', 'customer_site.site_id', '=', 'sites.id')->where('customer_site.customer_id', $customer_id)->select('sites.*')->orderBy('identifier','asc')->get();
            }
        }
    }

    public function customer_list()
    {
        return $this->customers->pluck('id')->toArray();
    }

    public function customer_names()
    {
        $names = [];
        foreach($this->customers as $customer){
            $names[$customer->id] = $customer->name;
        }
        return $names;
    }

    public function owner(){
        return $this->belongsToMany(Customer::class, 'customer_site')->wherePivot('owner', CustomerSite::OWNER_MAIN)->first();
    }

    public function owner_id(){
        return CustomerSite::where('site_id', $this->id)->where('owner',CustomerSite::OWNER_MAIN)->pluck('customer_id')->first();
    }

    public function colocate_ids(){
        $ids = [];
        foreach ($this->customers as $customer){
            if($customer->pivot->owner == CustomerSite::OWNER_SECONDARY){
                $ids[] = $customer->id;
            }
        }
        return $ids;
    }

    public function customerAntennas($customer_id){
        return Antenna::where('site_id',$this->id)->where('customer_id',$customer_id)->orderBy('antenna_order','asc')->get();
    }

    public function customerMicrowaves($customer_id){
        return Microwave::where('site_id',$this->id)->where('customer_id',$customer_id)->orderBy('microwave_order','asc')->get();
    }

    public function customerFwas($customer_id){
        return FWAntenna::where('site_id',$this->id)->where('customer_id',$customer_id)->get();
    }

    public function sectors($customer_id)
    {
        $sectors = [];
        foreach ($this->antennas as $antenna){
            if ($antenna->customer_id == $customer_id){
                if(in_array($antenna->sector, array_keys($sectors))){
                   $sectors[$antenna->sector][] = $antenna;
                } else {
                    $sectors[$antenna->sector] = [$antenna];
                }
            }
        }
        return $sectors;
    }

    public function photo(){
        $job = Job::where('company_id',config('company.id'))->where('site_id',$this->id)->where('status',Job::STATUS_COMPLETED)->orderBy('updated_at', 'desc')->first();
        if($job){
            return $job->sitePhoto();
        } else {
            return null;
        }
        /*
        $latest_photo_id = null;
        $latest_updated_at = null;
        $tasks = Task::where('company_id',config('company.id'))->whereIn('task_type',[Task::TYPE_PREVENTIVE,Task::TYPE_CORRECTIVE,TASK::TYPE_PLANNED])->get();
        foreach($tasks as $task){
            if($task->report_template){
                $answer = Answer::findAnyAnswer($task->report_template->front_photo_field_id,0,0,0,0);
                if($answer){
                    if($answer->updated_at > $latest_updated_at){
                        try{
                            $mediaJson = json_decode($answer->value);
                            $media_id = $mediaJson[0];
                        } catch (Exception $e){
                            $media_id = intval($answer->value);
                        }
                        if($media_id){
                            $latest_photo_id = $media_id;
                            $latest_updated_at = $answer->updated_at;
                        }
                    }
                }
            }
        }
        if($latest_photo_id){
            $media = Media::find($latest_photo_id);
            return $media->url;
        } else {
            return null;
        }*/
    }

    public static function microwaveSites($customer_id,$not_site_id)
    {
        return Site::whereHas('microwaves')->where('id','!=',$not_site_id)->get();
    }

    public function customers(){
        return $this->belongsToMany(Customer::class, 'customer_site')->withPivot('owner');
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }

    public function antennas(){
        return $this->hasMany(Antenna::class)->orderBy('antenna_order', 'asc');
    }

    public function microwaves(){
        return $this->hasMany(Microwave::class)->orderBy('microwave_order', 'asc');
    }

    public function fwas(){
        return $this->hasMany(FWAntenna::class)->orderBy('sector', 'asc');
    }

    public function apiSiteInfo()
    {
        return ['id'=>$this->id, 'identifier'=>$this->identifier, 'name'=>$this->name, 'island'=>$this->island, 'country'=>($this->country?$this->country->name:''), 'latitude'=>$this->latitude, 'longitude'=>$this->longitude, 'owner_id'=>$this->owner_id(), 'colocated_ids'=>$this->colocate_ids()];
    }
}
