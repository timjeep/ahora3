<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Exception;

class Answer extends Model
{
    use SoftDeletes;

    const SOURCE_UNKNOWN    = 0;
    const SOURCE_WEB        = 1;
    const SOURCE_APP_V1     = 2;
    const SOURCE_APP_V2     = 3;

    static $sourceStrings = [
        self::SOURCE_UNKNOWN    => "Unknown",
        self::SOURCE_WEB        => "Web",
        self::SOURCE_APP_V1     => "App V1",
        self::SOURCE_APP_V2     => "App V2",
    ];


    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'job_id',
        'field_id',
        'value',
    ];

    public $medias = [];

    public function sourceStr()
    {
        return self::$sourceStrings[$this->source];
    }

    public function selectValue($associative=false){
        // Select / Radio / Checkbox maybe json_encoded
        try {
            return json_decode($this->value, $associative);
        } catch (Exception $e){
            return $this->value;
        }
    }

/*    public static function fieldAnswer(int $job_id, int $field_id)
    {
        return Answer::where('job_id', $job_id)->where('field_id', $field_id)->orderBy('updated_at', 'desc')->first(); 
    }

    public static function fieldAnswers(int $job_id, int $field_id)
    {
        return Answer::where('job_id', $job_id)->where('field_id', $field_id)->orderBy('updated_at', 'asc')->get(); 
    }
*/
    public static function findAnyAnswer(int $field_id, int $antenna_id, int $port_id, int $radio_id, int $microwave_id=0, int $fwa_id=0)
    {
        return Answer::where('field_id', $field_id)->where('antenna_id', $antenna_id)->where('port_id',$port_id)->where('radio_id',$radio_id)->where('microwave_id',$microwave_id)->orderBy('updated_at', 'desc')->first();
    }

    public static function findAnswer(int $job_id, int $task_id, int $field_id, int $antenna_id, int $port_id, int $radio_id, int $microwave_id=0, int $fwa_id=0, int $user_id=0)
    {
        //Log::debug('findAnswer for job='.$job_id.', task='.$task_id.', field='.$field_id.', antenna='.$antenna_id.', port='.$port_id.', radio='.$radio_id.', microwave_id='.$microwave_id.', fwa_id='.$fwa_id.', user='.$user_id);
        $query = Answer::where('job_id', $job_id)->where('task_id',$task_id)->where('field_id', $field_id)->where('antenna_id', $antenna_id)->where('port_id',$port_id)->where('radio_id',$radio_id)->where('microwave_id',$microwave_id);
        if ($user_id){
            $query->where('user_id', $user_id);
        }
        return $query->orderBy('updated_at', 'desc')->first();
    }

    public static function getComments($media_id, $field_id)
    {
        $field = Field::find($field_id);
        if(!$field){
            return null;
        }
        if(!isset($field->options['comment']) || !$field->options['comment']){
            return null;
        }

        if(isset($field->options['multiple']) && $field->options['multiple']){
            $answers = Answer::where('field_id',$field_id)->where('value','LIKE',"%{$media_id}%")->get();
            foreach($answers as $answer){
                try {
                    $value = json_decode($answer->value,true);
                } catch (Exception $e){
                    return null;
                }
                if(is_array($value)){
                    if(in_array($media_id,$value)){
                        try {
                            $comments = json_decode($answer->comment,true);
                        } catch (Exception $e){
                            return null;
                        }
                        if(isset($comments[$media_id])){
                            return $comments[$media_id];
                        } else {
                            return null;
                        }
                    } else {
                        return null;
                    }
                } elseif ($value == $media_id){
                    return $answer->comment;
                } else {
                    return null;
                }
            }
            return null;
        } else {
            $answer = Answer::where('field_id',$field_id)->where('value',$media_id)->first();
            if($answer){
                return $answer->comment;
            } else {
                return null;
            }
        }
    }

    public function updateMedia($media_updates){
        if(is_array($media_updates)){
            foreach($media_updates as $media_id){
                $media = Media::find($media_id);
                if($media){
                    $media->setAnswer($this->id);
                } else {
                    Log::error('Company:'.config('company.id').' Answer::updateMedia (loop) - Unknown media_id='.$media_id.' for answer_id='.$this->id);
                }
            }    
        } else {
            $media = Media::find($media_updates);
            if($media){
                $media->setAnswer($this->id);
            } else {
                Log::error('Company:'.config('company.id').' Answer::updateMedia - Unknown media_id='.$media_updates.' for answer_id='.$this->id);
            }
        }
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function job(){
        return $this->belongsTo(Job::class);
    }

    public function task(){
        return $this->belongsTo(Task::class);
    }

    public function field(){
        return $this->belongsTo(Field::class);
    }

    public function media(){
        return $this->belongsTo(Media::class, 'value');
    }

    public function site(){
        return $this->belongsTo(Site::class);
    }

    public function antenna(){
        return $this->belongsTo(Antenna::class);
    }
    public function port(){
        return $this->belongsTo(Port::class);
    }
    public function radio(){
        return $this->belongsTo(Radio::class);
    }
    public function microwave(){
        return $this->belongsTo(Microwave::class);
    }
    public function fwa(){
        return $this->belongsTo(FWAntenna::class,'fwa_id');
    }

}
