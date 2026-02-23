<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Field;
use App\Models\Form;

class FormField extends Model
{
    const TYPE_FORM     = 1;
    const TYPE_FIELD    = 2;
    const TYPE_DECISION = 3;

    public static $typeStrings = [
        self::TYPE_FORM     => 'form',
        self::TYPE_FIELD    => 'field',
        self::TYPE_DECISION   => 'decision',
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
        'form_id',
        'field_id',
        'form_field_type',
        'field_order',
    ];

    public static function formFields($form_id)
    {
        return FormField::where('form_id', $form_id)->orderBy('field_order', 'asc')->get();
    }
/*
    public static function formFieldsWithAnswers($job_id, $form_id){
        $results = [];
        foreach(FormField::formFields($form_id) as $formfield){
            switch($formfield->form_field_type){
                case FormField::TYPE_FIELD:
                    $results[$formfield] = Answer::fieldAnswer($job_id, $formfield->model_id);
                    break;
                case FormField::TYPE_FORM:
                    $results[$formfield] = FormField::formFieldsWithAnswers($job_id, $formfield->model_id);
                    break;
            }
        }

        return $results;
    }*/

    public static function fromRelation($form_id, $formfield_type, $model_id){
        return FormField::where('form_id', $form_id)->where('form_field_type', $formfield_type)->where('model_id', $model_id)->first();
    }

    public static function sortedList($form_id){
        $fields = [];
        $form_fields = FormField::where('form_id',$form_id)->orderBy('field_order', 'asc')->get();
        foreach ($form_fields as $form_field){
            if ($form_field->form_field_type == FormField::TYPE_FORM) {
                $fields[] = Form::find($form_field->field_id);
            } elseif ($form_field->form_field_type == FormField::TYPE_FIELD) {
                $fields[] = Field::find($form_field->field_id);
            } else {

            }
        }
        return $fields;
    }

  /*  public function subforms()
    {
        return FormField::where('form_id',$this->id)->where('form_field_type', FormField::TYPE_FORM)->where('');
        DB::table('form_fields')
            ->join('forms', 'forms.id', '=', 'form_fields.model_id')
            ->where('form_field.field_type', FormField::TYPE_FORM)
            ->pluck('forms.id')
            ->toArray();
    }*/

    public static function allFieldList($form_id){
        return FormField::where('form_id',$form_id)->where('form_field_type', FormField::TYPE_FIELD)->orderBy('field_order', 'asc')->pluck('model_id')->toArray();
    }

    // Returns all Fields not already used by this form
    public static function unusedFieldList($form_id){
        return Field::where('company_id', config('company.id'))->whereNotIn('id',FormField::allFieldList($form_id))->get();
    }

    public static function allFormList($form_id){
        return FormField::where('form_id',$form_id)->where('form_field_type', FormField::TYPE_FORM)->orderBy('field_order', 'asc')->pluck('model_id')->toArray();
    }

    // Returns all Forms not already used by this form
    public static function unusedFormList($form_id){
        return Form::where('company_id', config('company.id'))->whereNotIn('id',FormField::allFormList($form_id))->get();
    }

    public static function lastOrder(int $form_id){
        $order = FormField::where('form_id', $form_id)->orderBy('field_order', 'desc')->pluck('field_order')->first();
        if($order){
            return $order;
        } else {
            return 0;
        }
    }

    public static function orderDown(int $form_id, int $down_formfield_id, int $before_formfield_id=0): int
    {
        if (!$down_formfield_id){
            $last_formfield = FormField::where('form_id', $form_id)->orderBy('field_order', 'desc')->first();
            if (empty($last_formfield)){
                return 1;
            } else {
                $order = $last_formfield->field_order+1;
            }
        } else {
            $down_formfield = FormField::find($down_formfield_id);
            if (empty($down_formfield)){
                return 1;
            }
            $order = $down_formfield->field_order;
        }

        if (!$before_formfield_id){
            $formfields = FormField::where('form_id', $form_id)->where('field_order','>=',$order)->orderBy('field_order', 'asc')->get();
            foreach($formfields as $formfield){
                if ($formfield->id == $before_formfield_id){
                    return $order;
                }
                $formfield->field_order++;
                $formfield->save();
            }
        } else {
            $before_formfield = FormField::findOrFail($before_formfield_id);
            if ($before_formfield->field_order > $order){
                $formfields = FormField::where('form_id', $form_id)->where('field_order','>=',$order)->orderBy('field_order', 'asc')->get();
                foreach($formfields as $formfield){
                    if ($formfield->id == $before_formfield_id){
                        return $order;
                    }
                    $formfield->field_order++;
                    $formfield->save();
                }    
            } else {
                $formfields = FormField::where('form_id', $form_id)->where('field_order','<',$order)->orderBy('field_order', 'desc')->get();
                foreach($formfields as $formfield){
                    if ($formfield->id == $before_formfield_id){
                        return $order-1;
                    }
                    $formfield->field_order--;
                    $formfield->save();
                }
    
            }
        }

        return $order;
    }

    public static function orderRemove(int $form_id, int $order)
    {
        $formfields = FormField::where('form_id', $form_id)->where('field_order','>',$order)->orderBy('field_order', 'asc')->get();
        foreach($formfields as $formfield){
            $formfield->field_order--;
            $formfield->save();
        }
    }

    public function field()
    {
        return $this->belongsTo(Field::class, 'model_id');
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'model_id');
    }

    public function decision()
    {
        return $this->belongsTo(Decision::class, 'model_id');
    }
}
