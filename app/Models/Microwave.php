<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Microwave extends Model
{
    use SoftDeletes;

    const STATUS_INACTIVE   = 1;
    const STATUS_ACTIVE     = 2;
    const STATUS_TECHADD    = 3;
    const STATUS_NOTPRESENT = 4;

    public static $statusStrings = [
        self::STATUS_INACTIVE   => 'Inactive',
        self::STATUS_ACTIVE     => 'Active',
        self::STATUS_TECHADD    => 'Tech Added',
        self::STATUS_NOTPRESENT => 'Not Present',
    ];

    public static $apiStatusStrings = [
        self::STATUS_INACTIVE   => 'inactive',
        self::STATUS_ACTIVE     => 'active',
        self::STATUS_TECHADD    => 'tech-added',
        self::STATUS_NOTPRESENT => 'not present',
    ];

    const POLARIZATION_HORIZONTAL   = 1;
    const POLARIZATION_VERTICAL     = 2;

    public static $polarizationStrings = [
        self::POLARIZATION_HORIZONTAL   => 'Horizontal',
        self::POLARIZATION_VERTICAL     => 'Vertical',
    ];

    public function statusString()
    {
        return isset(self::$statusStrings[$this->status])?self::$statusStrings[$this->status]:'Unknown';
    }

    public function polarizationString()
    {
        return isset(self::$polarizationStrings[$this->polarization])?self::$polarizationStrings[$this->polarization]:'Unknown';
    }

    public function apiPolarizationString()
    {
        return isset(self::$polarizationStrings[$this->polarization])?self::$polarizationStrings[$this->polarization]:null;
    }

    public function delete()
    {
        $microwaves = Microwave::where('site_id',$this->site_id)->where('customer_id',$this->customer_id)->where('microwave_order','>',$this->microwave_order)->get();
        foreach($microwaves as $microwave){
            $microwave->microwave_order--;
            $microwave->save();
        }
        parent::delete();
    }
/*
    public static function exists(int $site_id, $height)
    {
        $microwave = Microwave::where('site_id',$site_id)->where('height',$height)->first();//->where('antenna_model_id',$antenna_model_id)
        if($microwave){
            return $microwave->id;
        } else {
            return false;
        }
    }*/

    public static function updateHeight($microwave_id, $height)
    {
        $microwave = Microwave::findOrFail($microwave_id);
        $microwave->height = $height;
        $microwave->save();
    }

    public static function nextOrder($site_id, $customer_id)
    {
        $order =  Microwave::where('site_id', $site_id)->where('customer_id', $customer_id)->orderBy('microwave_order', 'desc')->pluck('microwave_order')->first();
        if($order===null){
            return 0;
        } else {
            return $order+1;
        }
    }

    public function move($newIndex)
    {
        $oldIndex = $this->microwave_order;

        if($oldIndex < $newIndex){
            $microwaves = Microwave::where('site_id', $this->site_id)->where('customer_id',$this->customer_id)->whereBetween('microwave_order', [$oldIndex+1, $newIndex])->get();
            foreach($microwaves as $microwave){
                $microwave->microwave_order--;
                $microwave->save();
            }
        } elseif($oldIndex > $newIndex){
            $microwaves = Microwave::where('site_id', $this->site_id)->where('customer_id',$this->customer_id)->whereBetween('microwave_order', [$newIndex, $oldIndex-1])->get();
            foreach($microwaves as $microwave){
                $microwave->microwave_order++;
                $microwave->save();
            }
        } else {
            // leave it where it is
            return;
        }

        $this->microwave_order = $newIndex;
        $this->save();
    }

    public function name()
    {
        return 'MW: '. $this->model .'-'. $this->height;
    }

    public function availableFarends()
    {
        $microwave_id = $this->id;
        return Microwave::where('id','!=',$this->id)->wheredoesnthave('farend', function (Builder $query) use ($microwave_id) {
            $query->where('microwave1_id', '!=', $microwave_id)->where('microwave2_id', '!=', $microwave_id);
        })->get();
    }

    public function syncFarend($farend_microwave_id)
    {
        // Check if it exists;
        $farend_microwave = $this->farend();
        if($farend_microwave && ($farend_microwave->id == $farend_microwave_id)){
            return;
        }

        // Delete any other relations to this microwave
        DB::table('microwave_farends')->where('microwave1_id', $this->id)->orWhere('microwave2_id',$this->id)->delete();

        // Delete any other relations to the farend microwave
        DB::table('microwave_farends')->where('microwave1_id', $farend_microwave_id)->orWhere('microwave2_id',$farend_microwave_id)->delete();

        // Add the new relation
        $this->farend1()->attach($farend_microwave_id);
    }

    public function site(){
        return $this->belongsTo(Site::class);
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function farend1(){
        return $this->belongsToMany(Microwave::class,'microwave_farends','microwave1_id','microwave2_id');
    }
    public function farend2(){
        return $this->belongsToMany(Microwave::class,'microwave_farends','microwave2_id','microwave1_id');
    }

    public function farend(){
        if($this->farend1){
            return $this->farend1->first();
        } elseif($this->farend2){
            return $this->farend2->first();
        } else {
            return null;
        }
    }

    public function hasFarend(){
        return (DB::table('microwave_farends')->where('microwave1_id', $this->id)->orWhere('microwave2_id',$this->id)->count() > 0);
    }

    public function apiMicrowaveInfo()
    {
        return ['id'=>$this->id, 'site_id'=>$this->site_id, 'customer_id'=>$this->customer_id, 'order'=>$this->microwave_order, 'status'=>$this->statusString(), 'model'=>$this->model, 'height'=>$this->height, 'txlinetype'=>$this->txlinetype, 'txlinelength'=>$this->txlinelength, 'size'=>$this->size, 'polarization'=>$this->apiPolarizationString(), 'azimuth'=>$this->azimuth, 'farend'=>$this->farend_microwave_id];
    }
}
