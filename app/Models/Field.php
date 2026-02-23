<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\FormField;
use Exception;

use function PHPUnit\Framework\returnSelf;

class Field extends Model
{
    use SoftDeletes;

    const UNITS_METRIC  = 1;
    const UNITS_SAE     = 2;

    public static $unitStrings = [
        self::UNITS_METRIC  => 'Metric',
        self::UNITS_SAE     => 'SAE',
    ];

    const UNITS_SHORT   = 1;
    const UNITS_LONG    = 2;

    public static $unitShortLong = [
        self::UNITS_SHORT   => 'mm/in',
        self::UNITS_LONG    => 'meter/foot',
    ];

    const UNITFORMAT_NORMAL     = 0;
    const UNITFORMAT_SHORT      = 1;
    const UNITFORMAT_COMPACT    = 2;

    const FIELDTYPE_BOOL            = 1;
    const FIELDTYPE_NUMBER          = 2;
    const FIELDTYPE_SELECT          = 3;
    const FIELDTYPE_TEXT            = 4;
    const FIELDTYPE_LONGTEXT        = 5;
    const FIELDTYPE_DATE            = 6;
    const FIELDTYPE_TIME            = 7;
    const FIELDTYPE_DATETIME        = 8;
    const FIELDTYPE_TEL             = 9;
    const FIELDTYPE_EMAIL           = 10;
    const FIELDTYPE_RANGE           = 11;
    const FIELDTYPE_MEDIA           = 12;
    const FIELDTYPE_ISSUELIST       = 13;
    const FIELDTYPE_GROUNDINGLAYOUT = 14;
    const FIELDTYPE_ELECTRICALPLAN  = 15;
    const FIELDTYPE_DISTANCE        = 16;
    const FIELDTYPE_PIPE            = 17;
    const FIELDTYPE_DELOADLIST      = 18;
    const FIELDTYPE_BATTERYTEST     = 19;
    const FIELDTYPE_OHMS            = 20;
    const FIELDTYPE_BILLOFMATERIAL  = 21;
    const FIELDTYPE_EMPTYFIELD      = 22;

    public static $fieldtype_strings = [
        self::FIELDTYPE_BOOL            => 'Bool / Checkbox',
        self::FIELDTYPE_NUMBER          => 'Number',
        self::FIELDTYPE_SELECT          => 'Select / Multi-Select / Radio',
        self::FIELDTYPE_TEXT            => 'Short Text',
        self::FIELDTYPE_LONGTEXT        => 'Long Text',
        self::FIELDTYPE_DATE            => 'Date',
        self::FIELDTYPE_TIME            => 'Time',
        self::FIELDTYPE_DATETIME        => 'Date Time',
        self::FIELDTYPE_TEL             => 'Telephone',
        self::FIELDTYPE_EMAIL           => 'E-Mail',
        self::FIELDTYPE_RANGE           => 'Range',
        self::FIELDTYPE_MEDIA           => 'File / Photo',
        self::FIELDTYPE_ISSUELIST       => 'Issue List',
        self::FIELDTYPE_GROUNDINGLAYOUT => 'Grounding Layout',
        self::FIELDTYPE_ELECTRICALPLAN  => 'Electrical Plan',
        self::FIELDTYPE_DISTANCE        => 'Distance / Length',
        self::FIELDTYPE_PIPE            => 'Pipe',
        self::FIELDTYPE_DELOADLIST      => 'Equipment Deload Table',
        self::FIELDTYPE_BATTERYTEST     => 'Battery Test',
        self::FIELDTYPE_OHMS            => 'Ohms',
        self::FIELDTYPE_BILLOFMATERIAL  => 'Bill of Material',
    ];

    public static $fieldtypeApi_strings = [
        self::FIELDTYPE_BOOL            => 'checkbox',
        self::FIELDTYPE_NUMBER          => 'number',
        self::FIELDTYPE_SELECT          => 'select',
        self::FIELDTYPE_TEXT            => 'short-text',
        self::FIELDTYPE_LONGTEXT        => 'long-text',
        self::FIELDTYPE_DATE            => 'date',      // not used
        self::FIELDTYPE_TIME            => 'time',      // not used
        self::FIELDTYPE_DATETIME        => 'date-time', // not used
        self::FIELDTYPE_TEL             => 'phone',     // not used
        self::FIELDTYPE_EMAIL           => 'email',     // not used
        self::FIELDTYPE_RANGE           => 'range',     // not used
        self::FIELDTYPE_MEDIA           => 'media',
        self::FIELDTYPE_ISSUELIST       => 'issues',
        self::FIELDTYPE_GROUNDINGLAYOUT => 'grounding',
        self::FIELDTYPE_ELECTRICALPLAN  => 'electrical',
        self::FIELDTYPE_DISTANCE        => 'distance',
        self::FIELDTYPE_PIPE            => 'pipe',
        self::FIELDTYPE_DELOADLIST      => 'deload',
        self::FIELDTYPE_BATTERYTEST     => 'battery',
        self::FIELDTYPE_OHMS            => 'ohms',
        self::FIELDTYPE_BILLOFMATERIAL  => 'bom',
    ];

    public static $severityStrings = [
        'H' => 'High',
        'M' => 'Medium',
        'L' => 'Low',
    ];

    public static $bomCategories = [
        "main"          =>'Main',
        "support-tower" =>'Support (Tower Steel)',
        "support-cable" =>'Support (Cable Fixing)',
        "power"         =>'Power',
        "grounding"     =>'Grounding',
        "acc"           =>'Accessories',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'array',
        'style' => 'array',
    ];

    public function class(){
        return 'field';
    }

    public function apiType(){
        return isset(self::$fieldtypeApi_strings[$this->field_type])?self::$fieldtypeApi_strings[$this->field_type]:'Unknown';
    }

    public function type(){
        return isset(self::$fieldtype_strings[$this->field_type])?self::$fieldtype_strings[$this->field_type]:'Unknown';
    }

/*    public function setBoolOptions (){
        $this->options = [];
    }

    public function setIntegerOptions ($lower, $upper){
        $this->options = ['lower'=>$lower, 'upper'=>$upper];
    }
    */


/*    public function fieldList(){
        return FormField::fieldList($this->id);
    }*/

    public static function distancePlaceholder($shortLong, $unitfmt=self::UNITFORMAT_NORMAL)
    {
        return self::formatUnits($shortLong, $unitfmt);
    }

    public static function unitStr($units)
    {
        return isset(Field::$unitStrings[$units])?Field::$unitStrings[$units]:'Unknown';
    }

    public static function formatUnits($shortLong, $unitfmt=self::UNITFORMAT_NORMAL, $units=null)
    {
        if (!$units){
            $units = self::units();
        }

        if ($shortLong==Field::UNITS_LONG){
            if ($units==Field::UNITS_METRIC){
                return $unitfmt?'m':'Meters';
            } else {
                return $unitfmt?'ft':'Feet';
            }
        } else {
            if ($units==Field::UNITS_METRIC){
                return $unitfmt?'mm':'MilliMeters';
            } else {
                return $unitfmt?'in':'Inches';
            }
        }
    }

    public static function formatDistance($value, $shortLong, $unitfmt=self::UNITFORMAT_NORMAL, $units=null)
    {
        $value = self::distanceFrom($value, $shortLong, $units);

        return $value.(($unitfmt==self::UNITFORMAT_COMPACT)?'':' ').self::formatUnits($shortLong, $unitfmt, $units);
    }

    public static function units()
    {
        if(config('customer.id')){
            return config('customer.units');
        } elseif (config('company.id')){
            return config('company.units');
        } else {
            return Field::UNITS_METRIC;
        }
    }

    /**
     * Converts value from Ft/M/in/mm to MM
     *
     * @param float/integer   $value  Value to convert
     * @param integer $shortLong Is it short mm/in or long m/ft, Field::UNITS_SHORT / Field::UNITS_LONG
     * @param integer $units Field::UNITS_METRIC / Field::UNITS_SAE
     * 
     * @return integer Converted value
     */ 
    public static function distanceTo($value, $shortLong, $units=null)
    {
        // Units are stored in mm
        if (!$units){
            $units = self::units();
        }
        $value = floatval($value);

        if($shortLong==Field::UNITS_LONG){
            if($units==Field::UNITS_SAE){
                // Converting from Feet to MM
                return round($value * 304.8,0);
            } else {
                // Converting from Meters to MM
                return $value * 1000;
            }
        } else {
            if($units==Field::UNITS_SAE){
                // Converting from Inches to MM
                return round($value * 25.4,0);
            } else {
                // Converting from MM to MM
                return $value;
            }
        }
    }

    /**
     * Converts value from MM to Ft/M/in/mm
     *
     * @param float/integer   $value  Value to convert
     * @param integer $shortLong Is it short mm/in or long m/ft, Field::UNITS_SHORT / Field::UNITS_LONG
     * @param integer $units Field::UNITS_METRIC / Field::UNITS_SAE
     * 
     * @return integer Converted value
     */ 
    public static function distanceFrom ($value, $shortLong, $units=null)
    {
        // Units are stored in mm
        if (!$units){
            $units = self::units();
        }
        $value = floatval($value);

        if($shortLong==Field::UNITS_LONG){
            if($units==Field::UNITS_SAE){
                // Converting from MM to Feet
                return round($value * 0.00328084, 1);
            } else {
                // Converting from MM to Meters
                return round($value * 0.001, 1);
            }
        } else {
            if($units==Field::UNITS_SAE){
                // Converting from MM to Inches
                return round($value * 0.0393701, 0);
            } else {
                // Converting from MM to MM
                return $value;
            }
        }
    }

    private function batteryEmpty($answer){
        if(empty($answer->value) && empty($answer->comment)){
            return true;
        } else {
            try {
                $value = json_decode($answer->value,true);
                if(!isset($value['count']) || !isset($value['strings'])){
                    return true;
                }
                foreach ($value['strings'] as $string){
                    if(isset($string['count']) && ($string['count']>0) && isset($string['batteries'])){
                        foreach ($string['batteries'] as $battery){
                            if(isset($battery['charging']) || isset($battery['discharging'])){
                                return false;
                            }
                        }
                    }
                }
                return true;
            } catch (Exception $e){
                return true;
            }
        }
        return false;
    }

    public function isEmpty($job_id, $task_id, $antenna_id=0, $port_id=0, $radio_id=0, $microwave_id=0, $fwa_id=0){
        $answer = Answer::findAnswer($job_id, $task_id, $this->id, $antenna_id, $port_id, $radio_id, $microwave_id, $fwa_id);
        if(!$answer){
            return true;
        }

        switch($this->field_type){
        
            case Field::FIELDTYPE_MEDIA:
                if(isset($this->options['multiple']) && $this->options['multiple']){
                    return (empty($answer->value) && empty($answer->comment));
                } else {
                    return ((empty($answer->value) || empty($answer->media)) && empty($answer->comment));
                }

            case Field::FIELDTYPE_BATTERYTEST:
                return $this->batteryEmpty($answer);
    
            default:
                return (empty($answer->value) && empty($answer->comment));
        }
    }

    public function value($answer, $customer){
        switch($this->field_type){
            case Field::FIELDTYPE_BOOL:
                return $answer->value?'Yes':'No';

            case Field::FIELDTYPE_NUMBER:
            case Field::FIELDTYPE_DATE:
            case Field::FIELDTYPE_TIME:
            case Field::FIELDTYPE_DATETIME:
            case Field::FIELDTYPE_RANGE:
            case Field::FIELDTYPE_TEXT:
            case Field::FIELDTYPE_LONGTEXT:
                return $answer->value;

            case Field::FIELDTYPE_SELECT:
                switch(isset($this->options['style'])?$this->options['style']:'select'){
                    case 'select':
                        if(isset($this->options['select'])){
                            foreach($this->options['select'] as $option){
                                if ($answer->selectValue() == $option['slug']) {
                                    if($option['other']){
                                        return $answer->other;
                                    } else {
                                        return $option['name'];
                                    }
                                }
                            }
                        }
                        return 'Unknown';
                case 'radio':
                    if(isset($this->options['multiple'])&&$this->options['multiple']){
                        $value = [];
                        if(isset($this->options['select'])){
                            $select = $this->options['select'];
                            $values = $answer->selectValue(true);
                            if(is_array($values)){
                                foreach($this->options['select'] as $option) {
                                    if (in_array($option['slug'], array_keys($values))) {
                                        if($option['other']){
                                            $value[] = $answer->other;
                                        } else {
                                            $value[] = $option['name'];
                                        }
                                    }
                                }
                            }
                        }
                        return $value;
                    } else {
                        if(isset($this->options['select'])){
                            foreach($this->options['select'] as $option) {
                                $test = $answer->value;
                                if ($answer->value == '"'.$option['slug'].'"') {
                                    if($option['other']){
                                        return $answer->other;
                                    } else {
                                        return $option['name'];
                                    }
                                }
                            }
                        }
                    }
                    return 'Unknown';
                }
        
            case Field::FIELDTYPE_MEDIA:
                if(isset($this->options['multiple'])&&$this->options['multiple']){
                    try{
                        $media_ids = json_decode($answer->value);
                        $medias = $answer->medias = [];
                        foreach ($media_ids as $media_id){
                            $media = Media::find($media_id);
                            $answer->medias[] = $media;
                            $medias[] = $media->url;
                        }
                        return $medias;
                    } catch (Exception $e){
                        return null;
                    }
                } else {
                    return $answer->media?$answer->media->url:null;
                }

            case FIELD::FIELDTYPE_DISTANCE:
                return self::formatDistance($answer->value, isset($this->options['units'])?$this->options['units']:Field::UNITS_LONG, Field::UNITFORMAT_SHORT, $customer->units);

            case Field::FIELDTYPE_PIPE:
                if(empty($answer->value)){
                    return [];
                } else {
                    try {
                        $answer->value = json_decode($answer->value,true);
                    } catch (Exception $e){
                        $answer->value = [];
                    }
                }
                return $answer->value;

            case Field::FIELDTYPE_BATTERYTEST:
            case Field::FIELDTYPE_ISSUELIST:
            case Field::FIELDTYPE_DELOADLIST:
            case Field::FIELDTYPE_BILLOFMATERIAL:
                if(empty($answer->value)){
                    return [];
                } else {
                    try {
                        $answer->value = json_decode($answer->value,true);
                    } catch (Exception $e){
                        $answer->value = [];
                    }
                }
                return $answer->value;
    
            default:
                return $answer->value;
        }
    }

    public function parentName()
    {
        $formField = FormField::where('form_field_type', FormField::TYPE_FIELD)->where('model_id',$this->id)->first();
        if ($formField){
            $form = Form::findOrFail($formField->form_id);
            return $form->name;
        } else {
            return 'None';
        }
    }

    public function parent()
    {
        $formField = FormField::where('form_field_type', FormField::TYPE_FIELD)->where('model_id',$this->id)->first();
        if ($formField){
            $form = Form::findOrFail($formField->form_id);
            return $form;
        } else {
            return null;
        }
    }

    public function parentNames($count=1)
    {
        $names = [];
        $parent = $this;
        while(($count-- > 0) && $parent){
            $parent = $parent->parent();
            if($parent){
                $names[] = $parent->name;
            }
        }
        return $names;
    }

    public function formCount()
    {
        return FormField::where('model_id', $this->id)->where('form_field_type', FormField::TYPE_FIELD)->count();
    }

    public function answer($job_id, $task_id, $antenna_id=0, $port_id=0,$radio_id=0, $microwave_id=0, $fwa_id=0)
    {
        return Answer::findAnswer($job_id, $task_id, $this->id, $antenna_id, $port_id, $radio_id, $microwave_id, $fwa_id);
    }

    public function answerValue($customer, $task_id, $job_id, $antenna_id=0, $port_id=0,$radio_id=0, $microwave_id=0, $fwa_id=0)
    {
        $answer = $this->answer($job_id, $task_id, $antenna_id, $port_id, $radio_id, $microwave_id, $fwa_id);
        if(empty($answer)){
            return '?';
        }
        return $this->value($answer,$customer);
    }

    public function media(){
        return $this->belongsTo(Media::class, 'model_id');
    }
}
