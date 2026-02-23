<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    const TYPE_PREVENTIVE           = 1;
    const TYPE_CORRECTIVE           = 2;
    const TYPE_PLANNED              = 3;
    const TYPE_VEHICLE_MAINTENANCE  = 4;
    const TYPE_PERSONAL_SAFETY      = 5;
    const TYPE_SITE_SAFETY          = 6;
    const TYPE_TOOLS                = 7;

    public static $typeStrings = [
        self::TYPE_PREVENTIVE           => 'Preventive Mtc',
        self::TYPE_CORRECTIVE           => 'Corrective Mtc',
        self::TYPE_PLANNED              => 'Planned Mtc',
        self::TYPE_VEHICLE_MAINTENANCE  => 'Vehicle Maintenance',
        self::TYPE_PERSONAL_SAFETY      => 'Personal Safety Equipment',
        self::TYPE_SITE_SAFETY          => 'Site Safety',
        self::TYPE_TOOLS                => 'Tools',
    ];

    const STATUS_NOTSTARTED     = 1;
    const STATUS_INPROGRESS     = 2;
    const STATUS_COMPLETED      = 3;

    public static $statusStrings = [
        self::STATUS_NOTSTARTED => 'Not Started',
        self::STATUS_INPROGRESS => 'In Progress',
        self::STATUS_COMPLETED  => 'Completed',
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

    public static function taskTypeString($task_type)
    {
        return isset(self::$typeStrings[$task_type])?self::$typeStrings[$task_type]:'Unknown';
    }

    public function taskType(){
        return self::taskTypeString($this->task_type);
    }

    public static function statusString($status)
    {
        return isset(self::$statusStrings[$status])?self::$statusStrings[$status]:'Unknown';
    }

    public function statusStr()
    {
        if(isset($this->pivot)){
            if($this->pivot->completed){
                return self::statusString(self::STATUS_COMPLETED);
            } elseif ($this->pivot->started){
                return self::statusString(self::STATUS_INPROGRESS);
            } else {
                return self::statusString(self::STATUS_NOTSTARTED);
            }
        } else {
            return self::statusString(self::STATUS_NOTSTARTED);
        }
    }

    public static function taskList()
    {
        $list = [''=>'Any'];
        foreach(Task::myTasks() as $task){
            $list[$task->id] = $task->name;
        }
        return $list;
    }

    public static function myTasks()
    {
        return Task::where('company_id', config('company.id'))->where('enabled',1)->get();
    }

    public static function possibleFrontPhotoFields($report_template_id)
    {
        $fields = new \Illuminate\Database\Eloquent\Collection();
        $tasks = Task::where('report_template_id',$report_template_id)->get();
        foreach ($tasks as $task){
            $forms = $task->form->allSubForms();
            $forms[] =$task->form;
            foreach ($forms as $form){
                $fields = $fields->merge($form->allSubFields(Field::FIELDTYPE_MEDIA));
            }
        }
        return $fields;
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function form(){
        return $this->belongsTo(Form::class);
    }
    public function antenna_form(){
        return $this->belongsTo(Form::class, 'antenna_form_id');
    }
    public function port_form(){
        return $this->belongsTo(Form::class, 'port_form_id');
    }
    public function radio_form(){
        return $this->belongsTo(Form::class, 'radio_form_id');
    }
    public function microwave_form(){
        return $this->belongsTo(Form::class, 'microwave_form_id');
    }
    public function fwa_form(){
        return $this->belongsTo(Form::class, 'fwa_form_id');
    }
    public function appendix_form(){
        return $this->belongsTo(Form::class, 'appendix_form_id');
    }

    public function report_template(){
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    public function jobs()
    {
        return $this->belongsToMany(Job::class);
    }

    public function apiTaskInfo($pivot=null)
    {
        return ['id'=>$this->id,
                'name'=>$this->name,
                'description'=>$this->description,
                'type'=>$this->taskType(),
                'site_form_id'=>$this->form_id,
                'antenna_form_id'=>$this->antenna_form_id,
                'port_form_id'=>$this->port_form_id,
                'radio_form_id'=>$this->radio_form_id,
                'microwave_form_id'=>$this->microwave_form_id,
                'fwa_form_id'=>$this->fwa_form_id,
                'appendix_form_id'=>$this->appendix_form_id,
                'loop_antenna' => !empty($this->antenna_form_id) || !empty($this->port_form_id) || !empty($this->radio_form_id),
                'loop_port' => !empty($this->port_form_id),
                'loop_radio' => !empty($this->radio_form_id),
                'loop_microwave' => !empty($this->microwave_form_id),
                'started'=>$pivot?$pivot->started:null,
                'completed'=>$pivot?$pivot->completed:null,
            ];
    }
}
