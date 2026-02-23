<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FWAntenna extends Model
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

    protected $table = 'fw_antennas';

    protected $casts = [
        'sector' => 'string',
    ];

    public static $sectorToLetter = [
        1 => 'A',
        2 => 'B',
    ];

    public function statusString()
    {
        return isset(self::$statusStrings[$this->status])?self::$statusStrings[$this->status]:'Unknown';
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
    
    public function sectorLetter()
    {
        return isset(self::$sectorToLetter[$this->sector])?self::$sectorToLetter[$this->sector]:'?';
    }

    public function cellName()
    {
        return substr($this->identifier,0,-2).'_'.round($this->frequency,1);
//        return $this->identifier.'_'.$this->sectorLetter();
        //return 'TA_'.($this->site?$this->site->identifier:'???').'_'.$this->sectorLetter();
    }

    public function sectorName()
    {
        return substr($this->identifier,0,-2).'_'.round($this->frequency,1).'_'.$this->sectorLetter();
    }

    public function sectorId()
    {
        return 'TA_'.($this->site?$this->site->identifier:'???').'_'.$this->sector;
    }

    public function name()
    {
        return $this->sectorName();
    }

    public function site(){
        return $this->belongsTo(Site::class);
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }
}
