<?php
namespace App\Models;

use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Port extends Model
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

    public static $gen = ['2g'=>'WCDMA','3g'=>'GSM','4g'=>'LTE'];

    public static $technologies = [
        'B2_1900',
        'B4_2100',
        'B5_850',
        'B7_2600',
        'B13_700',
    ];

    public static $generationStrings = ['2g'=>'2G','3g'=>'3G','4g'=>'4G','5g'=>'5G'];

    public static $generationAbbrevations = ['2g'=>'G', '3g'=>'W', '4g'=>'L', '5g'=>'C'];

    public static $techMappings = [
        'B2_1900'=>['2g'=>'B2_G1900','3g'=>'B2_W1900','4g'=>'B2_L1900'],
        'B4_AWS'=>['4g'=>'B4_AWS'],
        'B4_2100'=>['4g'=>'B4_L2100'],
        'B5_850'=>['2g'=>'B5_G850','3g'=>'B5_W850', '4g'=>'B5_L850'],
        'B7_2600'=>['4g'=>'B7_L2600'],
        'B13_700'=>['4g'=>'B13_L700'],
        'B17_700'=>['4g'=>'B17_L700'],
        'B42_TD3500'=>['4g'=>'B42_TD3500'],
    ];

    public static $techBackMappings = [
        'B5_G850'=>['generation'=>'2g', 'technology'=>'B5_850'],
        'B5_W850'=>['generation'=>'3g', 'technology'=>'B5_850'],
        'B5_L850'=>['generation'=>'4g', 'technology'=>'B5_850'],
        'B2_G1900'=>['generation'=>'2g', 'technology'=>'B2_1900'],
        'B2_W1900'=>['generation'=>'3g', 'technology'=>'B2_1900'],
        'B2_L1900'=>['generation'=>'4g', 'technology'=>'B2_1900'],
        'B17_L700'=>['generation'=>'4g', 'technology'=>'B17_700'],
        'B42_TD3500'=>['generation'=>'4g', 'technology'=>'B42_TD3500'],
        'B4_AWS'=>['generation'=>'4g', 'technology'=>'B4_AWS'],
        'B13_L700'=>['generation'=>'4g', 'technology'=>'B13_700'],
        'B4_L2100'=>['generation'=>'4g', 'technology'=>'B4_2100'],
        'B7_L2600'=>['generation'=>'4g', 'technology'=>'B7_2600'],
    ];

    public static $frequencyMappings = [
        'B2_1900'=>1900,
        'B4_AWS'=>3500,
        'B4_2100'=>2100,
        'B5_850'=>850,
        'B7_2600'=>2600,
        'B13_700'=>700,
        'B17_700'=>700,
        'B42_TD3500'=>3500,
    ];

    public function statusString()
    {
        return isset(self::$statusStrings[$this->status])?self::$statusStrings[$this->status]:'Unknown';
    }

    public static function generationStr($generation){
        return isset(self::$generationStrings[$generation])?self::$generationStrings[$generation]:'Unknown';
    }

    public function generationString(){
        return self::generationStr($this->generation);
    }

    public function techMap()
    {
        if(empty($this->technology) || empty($this->generation)){
            return 'Unknown';
        } elseif(isset(self::$techMappings[$this->technology][$this->generation])){
            return self::$techMappings[$this->technology][$this->generation];
        } else {
            Log::alert('Company:'.config('company.id').' Port::techMap - unknown mapping for technology='.$this->technology.' generation='.$this->generation);
            return 'Unknown';
        }
    }

    public function frequency()
    {
        if(isset(self::$frequencyMappings[$this->technology])){
            return self::$frequencyMappings[$this->technology];
        } else {
            Log::error('Company:'.config('company.id').' Port::frequency - Unknown technology='.$this->technology);
            return substr($this->technology, strpos($this->technology,'_')+1);
        }
    }

    public function gen()
    {
        return(isset(self::$gen[$this->generation])?self::$gen[$this->generation]:'???');
    }

    public function techMapBack($tech)
    {
        if(empty($tech)){
            $this->technology = null;
            $this->generation = null;
        }elseif(isset(self::$techBackMappings[$tech])){
            $this->technology = self::$techBackMappings[$tech]['technology'];
            $this->generation = self::$techBackMappings[$tech]['generation'];
        } else {
            Log::alert('Company:'.config('company.id').' Port::techMapBack - unknown mapping for tech='.$tech);
        }
    }

    public function name()
    {
        if(empty($this->technology)){
            return 'Unknown';
        } else {
            return $this->technology;
        }
    }

    public static function exists(int $antenna_id, $identifier)
    {
        $port = Port::where('antenna_id',$antenna_id)->where('identifier',$identifier)->first();
        if($port){
            return $port->id;
        } else {
            return false;
        }
    }

    public static function updateTechnology($port_id, $tech)
    {
        $port = Port::findorFail($port_id);
        $port->techMapBack($tech);
        $port->save();
        return true;
    }

    public function antenna(){
        return $this->belongsTo(Antenna::class);
    }

    public function apiPortInfo()
    {
        return ['id'=>$this->id, 'identifier'=>$this->identifier,'generation'=>$this->generation,'technology'=>$this->technology,'edt'=>$this->edt,'mapped'=>$this->techMap()];
    }

}
