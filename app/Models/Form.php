<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use App\Models\FormField;

class Form extends Model
{
    use SoftDeletes;

    const TYPE_SITE                 = 1;
    const TYPE_ANTENNA              = 2;
    const TYPE_CREW                 = 3;
    const TYPE_CERTS                = 4;
    const TYPE_PORT                 = 5;
    const TYPE_RADIO                = 6;
    const TYPE_APPENDIX             = 7;
    const TYPE_MICROWAVE            = 8;
    const TYPE_VEHICLE_MAINTENANCE  = 9;
    const TYPE_PERSONAL_SAFETY      = 10;
    const TYPE_SITE_SAFETY          = 11;
    const TYPE_TOOLS                = 12;
    const TYPE_FWA                  = 13;

    public static $typeStrings = [
        self::TYPE_SITE                 => 'Site',
        self::TYPE_ANTENNA              => 'Antenna',
        self::TYPE_PORT                 => 'Port',
        self::TYPE_RADIO                => 'Radio',
        self::TYPE_MICROWAVE            => 'Microwave',
        self::TYPE_APPENDIX             => 'Appendix',
        self::TYPE_CREW                 => 'Crew',
        self::TYPE_CERTS                => 'Certifications',
        self::TYPE_VEHICLE_MAINTENANCE  => 'Vehicle Maintenance',
        self::TYPE_PERSONAL_SAFETY      => 'Personal Safety Equipment Check',
        self::TYPE_SITE_SAFETY          => 'Site Safety Check',
        self::TYPE_TOOLS                => 'Tools',
        self::TYPE_FWA                  => 'Fixed Wireless Access',
    ];

    public static $compatibleTypes = [
        self::TYPE_SITE => [self::TYPE_SITE,self::TYPE_APPENDIX],
        self::TYPE_ANTENNA => [self::TYPE_ANTENNA],
        self::TYPE_CREW => [self::TYPE_CREW],
        self::TYPE_CERTS => [self::TYPE_CERTS],
        self::TYPE_PORT => [self::TYPE_PORT],
        self::TYPE_RADIO => [self::TYPE_RADIO],
        self::TYPE_APPENDIX => [self::TYPE_APPENDIX,self::TYPE_SITE],
        self::TYPE_MICROWAVE => [self::TYPE_MICROWAVE],
        self::TYPE_VEHICLE_MAINTENANCE => [self::TYPE_VEHICLE_MAINTENANCE],
        self::TYPE_PERSONAL_SAFETY => [self::TYPE_PERSONAL_SAFETY],
        self::TYPE_SITE_SAFETY => [self::TYPE_SITE_SAFETY],
        self::TYPE_TOOLS => [self::TYPE_TOOLS],
        self::TYPE_FWA => [self::TYPE_FWA],
    ];

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
    ];

    public function typeString()
    {
        return isset(self::$typeStrings[$this->form_type])?self::$typeStrings[$this->form_type]:'Unknown';
    }

    public function lastUpdated($check=false,$updated_at=null)
    {
        if(!$check){
            return $this->updated_at;
        }

        if(($updated_at==null) || ($this->updated_at > $updated_at)){
            $updated_at = $this->updated_at;
        }

        foreach($this->form_fields as $formfield){
            if($formfield->updated_at > $updated_at){
                $updated_at = $formfield->updated_at;
            }
            switch($formfield->form_field_type){
                case FormField::TYPE_FORM:
                    $updated_at = $formfield->form->lastUpdated($check, $updated_at);
                    break;
                case FormField::TYPE_FIELD:
                    if($formfield->field->updated_at > $updated_at){
                        $updated_at = $formfield->field->updated_at;
                    }
                    break;
                case FormField::TYPE_DECISION:
                    foreach($formfield->decision->options as $decision_key=>$decision_form_id){
                        $decision_form = Form::find($decision_form_id);
                        if($decision_form){
                            $updated_at = $decision_form->lastUpdated($check, $updated_at);
                        }
                    }
                    break;
                default:
                    Log::alert('Form::lastUpdated - Unknown form_field_type='.$formfield->form_field_type.' for formfield->id='.$formfield->id);
                    break;
            }
        }

        if(($updated_at > $this->updated_at) && ($this->subform==false)){
            Log::info('Updating Last Update of Form:'.$this->id.' to '.$updated_at);
            $this->updated_at = $updated_at;
            $this->save();
        }
        
        return $updated_at;
    }
    
    public static function myCertForms()
    {
        return Form::where('company_id', config('company.id'))->where('form_type', Form::TYPE_CERTS)->get();
    }

    public static function compatibleForms($form_type)
    {
        if(isset(self::$compatibleTypes[$form_type])){
            return self::$compatibleTypes[$form_type];
        } else {
            return [];
        }
    }

    public static function subForms($form_id, $form_type){
        return DB::table('form_fields')
            ->join('forms', 'forms.id', '=', 'form_fields.model_id')
            ->where('form_fields.form_id', $form_id)
            ->where('form_fields.form_field_type', FormField::TYPE_FORM)
            ->whereIn('forms.form_type', self::compatibleForms($form_type))
            ->pluck('forms.id')
            ->toArray();
    }

    public function allSubFormIds()
    {
        $subform_ids = [];
        foreach(Form::subForms($this->id, $this->form_type) as $subform_id){
            $subform_ids[] = $subform_id;
            $subform = Form::findOrFail($subform_id);
            $subform_ids = array_merge($subform_ids, $subform->allSubFormIds());
        }
        return $subform_ids;
    }

    public function allSubForms()
    {
        $subforms=[];
        foreach(Form::subForms($this->id, $this->form_type) as $subform_id){
            $subform = Form::findOrFail($subform_id);
            $subforms[] = $subform;
            $subforms = array_merge($subforms, $subform->allSubForms());
        }
        return $subforms;
    }

    public static function unusedSubForms($form_id){
        if(!$form_id){
            return [];
        }
        $form = Form::findOrFail($form_id);
        $subFormIds = $form->allSubFormIds();
        $subFormIds[] = $form_id;
        return  Form::where('form_type', $form->form_type)->whereNotIn('id', $subFormIds)->get();
    }

    public function allSubFieldIds()
    {
        $subFormIds = $this->allSubFormIds();
        $subFormIds[] = $this->id;
        return FormField::where('form_field_type', FormField::TYPE_FIELD)->whereIn('form_id', $subFormIds)->pluck('model_id')->toArray();
    }

    public function allSubFields($field_type=null)
    {
        if($field_type){
            return Field::whereIn('id',$this->allSubFieldIds())->where('field_type',$field_type)->get();
        } else {
            return Field::whereIn('id',$this->allSubFieldIds())->get();
        }
    }

    public static function unusedSubFields($form_id){
        if(!$form_id){
            return [];
        }
        $form = Form::findOrFail($form_id);
        $subfields = $form->allSubFieldIds();
        return Field::whereNotIn('id', $subfields)->get();
    }

    public function childrenCount()
    {
        return FormField::where('form_id', $this->id)->count();
    }

    public function parents()
    {
        $forms = [];
        if($this->id){
            $formFields = FormField::where('form_field_type', FormField::TYPE_FORM)->where('model_id',$this->id)->get();
            if ($formFields){
                foreach($formFields as $formField){
                    $forms[] = Form::findOrFail($formField->form_id);
                }
            }
        }
        return $forms;
    }

    public function parent()
    {
        $formField = FormField::where('form_field_type', FormField::TYPE_FORM)->where('model_id',$this->id)->first();
        if ($formField){
            $form = Form::findOrFail($formField->form_id);
            return $form;
        } else {
            return null;
        }
    }

    public function topForm()
    {
        if ($this->subform){
            $parent = $this->parent();
            if($parent){
                return $parent->topForm();
            } else {
                return $this; 
            }
        } else {
            return $this;
        }
    }

    public function unusedFieldList(){
        return Field::where('company_id', config('company.id'))->whereNotIn('id',$this->allSubFieldIds())->get();
    }

    public function parentCount(){
        return FormField::where('form_field_type',FormField::TYPE_FORM)->where('model_id',$this->id)->count();
    }

    public function fields(){
        return FormField::formFields($this->id);
    }

    public static function myForms($form_type=null){
        if($form_type){
            return Form::where('company_id', config('company.id'))->where('subform', false)->where('form_type', $form_type)->get();
        } else {
            return Form::where('company_id', config('company.id'))->where('subform', false)->get();
        }
    }

    public static function myVehicleMaintenanceForms()
    {
        return Form::myForms(self::TYPE_VEHICLE_MAINTENANCE);
    }

    public static function myPersonalSafetyForms()
    {
        return Form::myForms(self::TYPE_PERSONAL_SAFETY);
    }

    public static function mySiteSafetyForms()
    {
        return Form::myForms(self::TYPE_SITE_SAFETY);
    }

    public static function myToolForms()
    {
        return Form::myForms(self::TYPE_TOOLS);
    }

    public static function mySiteForms()
    {
        return Form::myForms(self::TYPE_SITE);
    }

    public static function myAntennaForms()
    {
        return Form::myForms(self::TYPE_ANTENNA);
    }

    public static function myPortForms()
    {
        return Form::myForms(self::TYPE_PORT);
    }

    public static function myRadioForms()
    {
        return Form::myForms(self::TYPE_RADIO);
    }

    public static function myMicrowaveForms()
    {
        return Form::myForms(self::TYPE_MICROWAVE);
    }

    public static function myFwaForms()
    {
        return Form::myForms(self::TYPE_FWA);
    }

    public static function myAppendixForms()
    {
        return Form::myForms(self::TYPE_APPENDIX);
    }

    public function allFields(){
        return FormField::where('form_id',$this->id)->where('form_field_type', FormField::TYPE_FIELD)->orderBy('field_order', 'asc')->get();
    }

    public function fieldList(){
        return FormField::sortedList($this->id);
    }

    public function form_fields(){
        return $this->hasMany(FormField::class)->orderBy('form_fields.field_order', 'ASC');
    }
}
