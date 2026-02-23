<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use App\Models\Answer;
use Exception;

class Decision extends Model
{
    use SoftDeletes;


    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options' => 'json',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
    ];

    public function form($job_id, $task_id, $antenna_id=0, $port_id=0, $radio_id=0, $microwave_id=0, $fwa_id=0)
    {
        $answer = Answer::findAnswer($job_id, $task_id, $this->field_id, $antenna_id, $port_id, $radio_id, $microwave_id, $fwa_id);
        if(!$answer){
            return null;
        }

        try{
            $value = json_decode($answer->value, true);
        } catch (Exception $e){
            return null;
        }

        foreach($this->options as $option=>$form_id){
            if(!empty($form_id)){
                if(is_array($value)){
                    if (in_array($option, $value)){
                        $form = Form::find($form_id);
                        if(!$form){
                            Log::alert('Company:'.config('company.id'). ' Decision::form - unknown form in decision, value='. $answer->value .', form_id='.$form_id);
                            return null;
                        }
                        return $form;
                    }
                } else {
                    if($option == $value){
                        $form = Form::find($form_id);
                        if(!$form){
                            Log::alert('Company:'.config('company.id'). ' Decision::form - unknown form in decision, value='. $answer->value .', form_id='.$form_id);
                            return null;
                        }
                        return $form;
                    }
                }
            }
        }

        return null;
    }

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
