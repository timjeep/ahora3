<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Antenna extends Model
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

    protected $casts = [
        'sector' => 'string',
    ];

    public function statusString()
    {
        return isset(self::$statusStrings[$this->status])?self::$statusStrings[$this->status]:'Unknown';
    }

    public function delete()
    {
        $antennas = Antenna::where('site_id',$this->site_id)->where('customer_id',$this->customer_id)->where('antenna_order','>',$this->antenna_order)->get();
        foreach($antennas as $antenna){
            $antenna->antenna_order--;
            $antenna->save();
        }
        parent::delete();
    }

    public static function exists(int $site_id, $antenna_model_id, $height, $azimuth, $sector)
    {
        $antenna = Antenna::where('site_id',$site_id)->where('height',$height)->where('sector',$sector)->where('azimuth',$azimuth)->first();//->where('antenna_model_id',$antenna_model_id)
        if($antenna){
            return $antenna->id;
        } else {
            return false;
        }
    }

    public function myActiveJobSiteAntenna($user_id=null)
    {
        return $this->site->myActiveJobSite($user_id);
    }

    public static function updateSector($antenna_id, $sector)
    {
        $antenna = Antenna::findOrFail($antenna_id);
        $antenna->sector = $sector;
        $antenna->save();
    }

    public static function updateAzimuth($antenna_id, $azimuth)
    {
        $antenna = Antenna::findOrFail($antenna_id);
        $antenna->azimuth = $azimuth;
        $antenna->save();
    }

    public static function updateHeight($antenna_id, $height)
    {
        $antenna = Antenna::findOrFail($antenna_id);
        $antenna->height = $height;
        $antenna->save();
    }

    public static function updateMDT($antenna_id, $mdt)
    {
        $antenna = Antenna::findOrFail($antenna_id);
        $antenna->mdt = $mdt;
        $antenna->save();
    }

    public static function nextOrder($site_id, $customer_id)
    {
        $order =  Antenna::where('site_id', $site_id)->where('customer_id', $customer_id)->orderBy('antenna_order', 'desc')->pluck('antenna_order')->first();
        if($order===null){
            return 0;
        } else {
            return $order+1;
        }
    }

    public function move($newIndex)
    {
        $oldIndex = $this->antenna_order;

        if($oldIndex < $newIndex){
            $antennas = Antenna::where('site_id', $this->site_id)->where('customer_id',$this->customer_id)->whereBetween('antenna_order', [$oldIndex+1, $newIndex])->get();
            foreach($antennas as $antenna){
                $antenna->antenna_order--;
                $antenna->save();
            }
        } elseif($oldIndex > $newIndex){
            $antennas = Antenna::where('site_id', $this->site_id)->where('customer_id',$this->customer_id)->whereBetween('antenna_order', [$newIndex, $oldIndex-1])->get();
            foreach($antennas as $antenna){
                $antenna->antenna_order++;
                $antenna->save();
            }
        } else {
            // leave it where it is
            return;
        }

        $this->antenna_order = $newIndex;
        $this->save();
    }

    public function technologies()
    {
        return Port::where('antenna_id',$this->id)->pluck('technology')->toArray();
    }

    public function portNames()
    {
        return Port::where('antenna_id',$this->id)->pluck('identifier')->toArray();
    }

    public function ports()
    {
        return $this->hasMany(Port::class);
    }

    public function radios()
    {
        return $this->hasMany(Radio::class);
    }

    public function name($identifier=null)
    {
        return ($identifier?($identifier.' '):'').'Sector: '.$this->sector .', Height: '.Field::distanceFrom($this->height, Field::UNITS_LONG) . strToLower(Field::formatUnits(Field::UNITS_LONG, Field::UNITFORMAT_SHORT));
    }

    public function site(){
        return $this->belongsTo(Site::class);
    }

    public function antenna_model(){
        return $this->belongsTo(AntennaModel::class, 'antenna_model_id');
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function apiAntennaInfo()
    {
        $ports = [];
        foreach($this->ports as $port){
            $ports[] = $port->apiPortInfo();
        }
        $radios = [];
        foreach($this->radios as $radio){
            $radios[] = $radio->apiRadioInfo();
        }
        return ['id'=>$this->id,'order'=>$this->antenna_order,'sector'=>$this->sector,'azimuth'=>$this->azimuth,'height'=>$this->height,'mdt'=>$this->mdt,'ports'=>$ports,'radios'=>$radios];
    }
}
