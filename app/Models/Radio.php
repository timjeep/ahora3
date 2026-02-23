<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Radio extends Model
{
    use SoftDeletes;

    const STATUS_INACTIVE   = 1;
    const STATUS_ACTIVE     = 2;
    const STATUS_TECHADD    = 3;

    public static $statusStrings = [
        self::STATUS_INACTIVE   => 'Inactive',
        self::STATUS_ACTIVE     => 'Active',
        self::STATUS_TECHADD    => 'Tech Added',
    ];

    public static $technologies = [
        'B2_1900',
        'B4_2100',
        'B5_850',
        'B7_2600',
        'B13_700',
        'B17_700',
    ];

    public static $technologyMap = [
        'B2_1900' => '1900MHz',
        'B4_2100' => '2100MHz',
        'B5_850' => '850MHz',
        'B7_2600' => '2600MHz',
        'B13_700' => '700MHz',
        'B17_700' => '700MHz',
        'B4_AWS' => '2100MHz',
    ];

    public function frequency()
    {
        return (isset(self::$technologyMap[$this->technology])?self::$technologyMap[$this->technology]:$this->technology);
    }

    public function techMap()
    {
        if(empty($this->technology) || empty($this->generation)){
            return 'Unknown';
        } elseif(isset(Port::$techMappings[$this->technology][$this->generation])){
            return Port::$techMappings[$this->technology][$this->generation];
        } else {
            Log::alert('Company:'.config('company.id').' Radio::techMap - unknown mapping for technology='.$this->technology.' generation='.$this->generation);
            return 'Unknown';
        }
    }

    public function techMapBack($tech)
    {
        if(empty($tech)){
            $this->technology = null;
            $this->generation = null;
        }elseif(isset(Port::$techBackMappings[$tech])){
            $this->technology = Port::$techBackMappings[$tech]['technology'];
            $this->generation = Port::$techBackMappings[$tech]['generation'];
        } else {
            Log::alert('Company:'.config('company.id').' Radio::techMapBack - unknown mapping for tech='.$tech);
        }
    }


    public function statusString()
    {
        return isset(self::$statusStrings[$this->status])?self::$statusStrings[$this->status]:'Unknown';
    }

    public function gen()
    {
        return(isset(Port::$gen[$this->generation])?Port::$gen[$this->generation]:'???');
    }

    public function name()
    {
        if(empty($this->technology)){
            return 'Unknown';
        } else {
            return $this->technology;
        }
    }

    public static function updateTechnology($radio_id, $tech)
    {
        $radio = Radio::findorFail($radio_id);
        $radio->techMapBack($tech);
        $radio->save();
        return true;
    }

    public function antenna(){
        return $this->belongsTo(Antenna::class);
    }
    
    public function apiRadioInfo()
    {
        return ['id'=>$this->id,'generation'=>$this->generation,'technology'=>$this->technology,'mapped'=>$this->techMap()];
    }
}
