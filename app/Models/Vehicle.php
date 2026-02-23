<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Vehicle extends Model
{
    use SoftDeletes;

    const STATUS_INACTIVE   = 1;
    const STATUS_ACTIVE     = 2;

    static $statusStrings = [
        self::STATUS_INACTIVE   => 'Inactive',
        self::STATUS_ACTIVE     => 'Active',
    ];

    static $apiStatusStrings = [
        self::STATUS_INACTIVE   => 'inactive',
        self::STATUS_ACTIVE     => 'active',
    ];

    public function statusStr()
    {
        if(isset(self::$statusStrings[$this->status])){
            return self::$statusStrings[$this->status];
        } else {
            return 'Unknown';
        }
    }

    public function apiStatusStr()
    {
        if(isset(self::$apiStatusStrings[$this->status])){
            return self::$apiStatusStrings[$this->status];
        } else {
            return 'unknown';
        }
    }

    public static function apiStrStatus($status)
    {
        foreach(self::$apiStatusStrings as $statusKey => $apiStatus){
            if($apiStatus == $status){
                return $statusKey;
            }
        }
        Log::alert('Company:'.config('company.id').' Vehicle::apiStrStatus - Unknown status ='.$status);
        return self::STATUS_ACTIVE;
    }

    public function identifier()
    {
        return $this->year .' '. $this->name .' '. $this->licence;
    }

    public static function vehicles()
    {
        return Vehicle::where('company_id',config('company.id'))->get();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
    public function apiVehicleInfo()
    {
        return ['id'=>$this->id, 'status'=>$this->apiStatusStr(), 'name'=>$this->name, 'licence'=>$this->licence, 'user'=>$this->user?->apiUserInfo('Company',true), 'year'=>$this->year, 'engine'=>$this->engine,'insurer'=>$this->insurer, 'insurance_expiry'=>$this->insurance_expires];
    }
}
