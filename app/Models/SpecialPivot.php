<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\Answer;
use Exception;

class SpecialLayout extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'attributes' => 'json',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
    ];

    const SPECIAL_BLANKMEDIA            = 1;
    const SPECIAL_SITUATINGMAP          = 2;
    const SPECIAL_OBSTRUCTIONDIAGRAM    = 3;
    const SPECIAL_FWACONFIGURATION      = 4;
    const SPECIAL_FWADIAGRAM            = 5;
    const SPECIAL_VERTICALSPACE         = 6;
    const SPECIAL_EMPTYFIELD            = 7;
    const SPECIAL_TABLEHEADER           = 8;
    const SPECIAL_SITEINFO              = 9;

    public static $specialStrings = [
        self::SPECIAL_BLANKMEDIA            => 'Blank Media',
        self::SPECIAL_SITUATINGMAP          => 'Situating Map',
        self::SPECIAL_OBSTRUCTIONDIAGRAM    => 'Obstruction Diagram',
        self::SPECIAL_FWACONFIGURATION      => 'FWA Configuration',
        self::SPECIAL_FWADIAGRAM            => 'FWA Diagram',
        self::SPECIAL_VERTICALSPACE         => 'Vertical Space',
        self::SPECIAL_EMPTYFIELD            => 'Empty Field',
        self::SPECIAL_TABLEHEADER           => 'Table Header',
        self::SPECIAL_SITEINFO              => 'Site Info',
    ];

    public static function specialString($special_id)
    {
        if(isset(self::$specialStrings[$special_id])){
            return self::$specialStrings[$special_id];
        } else {
            return '?? ('.$special_id.')';
        }
    }

    public function specialStr()
    {
        return $this->specialString($this->special_type);
    }

    public function class(){
        return 'special';
    }

    public function name(){
        return self::specialString($this->special_type);
    }
}
