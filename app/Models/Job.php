<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Job extends Model
{
    use SoftDeletes;

    const STATUS_CREATED    = 1;
    const STATUS_ASSIGNED   = 2;
    const STATUS_ACCEPTED   = 3;
    const STATUS_STARTED    = 4;
    const STATUS_SUSPENDED  = 5;
    const STATUS_COMPLETED  = 6;
    const STATUS_CANCELLED  = 7;
    const STATUS_CLOSED     = 8;

    public static $statusStrings = [
        self::STATUS_CREATED    => 'Created',
        self::STATUS_ASSIGNED   => 'Assigned',
        self::STATUS_ACCEPTED   => 'Accepted',
        self::STATUS_SUSPENDED  => 'Suspended',
        self::STATUS_STARTED    => 'In Progress',
        self::STATUS_COMPLETED  => 'Completed',
        self::STATUS_CANCELLED  => 'Cancelled',
        self::STATUS_CLOSED     => 'Closed',
    ];

    public static $validStatus = [
        self::STATUS_CREATED    => [self::STATUS_CREATED, self::STATUS_ASSIGNED, self::STATUS_CANCELLED],
        self::STATUS_ASSIGNED   => [self::STATUS_CREATED, self::STATUS_ASSIGNED, self::STATUS_ACCEPTED],
        self::STATUS_ACCEPTED   => [self::STATUS_ACCEPTED, self::STATUS_SUSPENDED, self::STATUS_STARTED],
        self::STATUS_SUSPENDED  => [self::STATUS_SUSPENDED, self::STATUS_STARTED, self::STATUS_CANCELLED, self::STATUS_COMPLETED],
        self::STATUS_STARTED    => [self::STATUS_SUSPENDED, self::STATUS_STARTED, self::STATUS_COMPLETED],
        self::STATUS_COMPLETED  => [self::STATUS_STARTED, self::STATUS_COMPLETED, self::STATUS_CLOSED],
        self::STATUS_CANCELLED  => [self::STATUS_STARTED, self::STATUS_CANCELLED],
        self::STATUS_CLOSED     => [self::STATUS_COMPLETED,self::STATUS_CLOSED],
    ];

    public static $activeStatus = [self::STATUS_ASSIGNED, self::STATUS_ACCEPTED, self::STATUS_STARTED, self::STATUS_SUSPENDED];
    public static $finishedStatus = [self::STATUS_COMPLETED, self::STATUS_CLOSED];

    public static $apiStatusStrings = [
        self::STATUS_CREATED    => 'created',
        self::STATUS_ASSIGNED   => 'assigned',
        self::STATUS_ACCEPTED   => 'accepted',
        self::STATUS_SUSPENDED  => 'suspended',
        self::STATUS_STARTED    => 'inprogress',
        self::STATUS_COMPLETED  => 'completed',
        self::STATUS_CANCELLED  => 'cancelled',
        self::STATUS_CLOSED     => 'closed',
    ];

    const TYPE_SITE     = 1;
    const TYPE_VEHICLE  = 2;
    const TYPE_PERSONAL = 3;

    public static $typeStrings = [
        self::TYPE_SITE     => 'Site',
        self::TYPE_VEHICLE  => 'Vehicle',
        self::TYPE_PERSONAL => 'Personal',
    ];

    const WATERMARK_NONE        = 0;
    const WATERMARK_PARTIAL     = 1;
    const WATERMARK_COMMENTS    = 2;

    public static $watermarkStrings = [

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
     //   'crew' => 'array',
    //    'task_ids'=>'json',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'status',
    ];

    public static function theStatus($status){
        return isset(self::$statusStrings[$status])?self::$statusStrings[$status]:'Unknown';
    }

    public function getStatus(){
        return self::theStatus($this->status);
    }

    public function apiStatus(){
        return isset(self::$apiStatusStrings[$this->status])?self::$apiStatusStrings[$this->status]:'unknown';
    }

    public function taskStatus($task_id)
    {
        $task = $this->tasks()->where('id', $task_id)->first();
        if($task){
            return $task->statusStr();
        } else {
            return Task::statusString(self::STATUS_CREATED);
        }
    }

    public static function jobTypeString($job_type)
    {
        return isset(self::$typeStrings[$job_type])?self::$typeStrings[$job_type]:'Unknown';
    }

    public function jobType(){
        return self::jobTypeString($this->job_type);
    }

    public function delete()
    {
        foreach($this->quotes as $quote){
            $quote->delete();
        }
        parent::delete();
    }

    public function mine()
    {
        foreach($this->users as $user){
            if($user->id == Auth::id()){
                return true;
            }
        }
        return false;
    }

    public function assignedDateFmt(){
        if($this->assigned===null){
            return 'Not Set';
        }
        $dt = Carbon::parse($this->assigned);
        if($dt->isToday()){
            return 'Today';
        } else {
            return $dt->toFormattedDateString();
        }
    }

    public function completedDate()
    {
        if($this->completed===null){
            return 'Not Completed';
        }
        $dt = Carbon::parse($this->completed);
        return $dt->format('Y-m-d');
    }

    public static function myActiveJobs()
    {
        return Job::where('company_id', config('company.id'))
                ->whereHas('users', function ($query) {
                    $query->where('users.id', '=', Auth::id());
                })
                ->whereIn('status',self::$activeStatus)->get();
    }

    public static function myActiveJobSiteList()
    {
        return Job::where('company_id', config('company.id'))
                ->whereHas('users', function ($query) {
                    $query->where('users.id', '=', Auth::id());
                })
                ->whereIn('status',self::$activeStatus)
                ->select('site_id')->distinct()->toArray();
    }

    public function myActiveJob($user_id=null)
    {
        if(!in_array($this->status,JOB::$activeStatus)){
            return false;
        }

        if(!$user_id){
            $user_id = Auth::id();
        }

        foreach($this->users as $user){
            if($user->id == $user_id){
                return true;
            }
        }
        return false;
    }

    public static function customerJobs($customer_id)
    {
        return Job::where('company_id', config('company.id'))->where('customer_id',$customer_id)->get();
    }

    public static function customerJobsCompleted($customer_id,$job_id=false)
    {
        $query = Job::where('company_id', config('company.id'))->where('customer_id',$customer_id)->where('status',self::STATUS_COMPLETED);
        if($job_id){
            $query->orWhere('id',$job_id);
        }
        return $query->get();
    }
/*
    public function percentComplete()
    {
        $task = $this->task()->withTrashed()->first();
        if(empty($task)){
            return 0;
        }
        $form = $task->form->withTrashed()->first();
        if(empty($form)){
            return 0;
        }
        $fieldIds = $form->allSubFieldIds();
        $numFields = count($fieldIds);
        // There might be more than one answer submitted (different user) per field, so need to check each one individually
        $numAnswers = 0;
        foreach ($fieldIds as $field_id){
            if (Answer::where('job_id', $this->id)->where('field_id', $field_id)->count() >0 )
            {
                $numAnswers++;
            }
        }
        return floor($numAnswers / $numFields * 100);
    }*/

    public function firstLast($task_id=null)
    {
        if(empty($this->first_answer)){
            $this->first_answer = now();
        }
        $this->last_answer = now();
        if ($this->status==Job::STATUS_ACCEPTED){
            $this->status = Job::STATUS_STARTED;
        }
        $this->save();

        if($task_id){
            try {
                //$num = Answer::where('job_id', $this->id)->where('task_id',$task_id)->count();
                $num = DB::table('job_task')->where('job_id',$this->id)->where('task_id',$task_id)->whereNotNull('first_answer')->count();
                if(!$num){
                    $this->tasks()->updateExistingPivot($task_id, [
                        'first_answer' => now(),
                        'last_answer' => now(),
                    ]);
                } else {
                    $this->tasks()->updateExistingPivot($task_id, [
                        'last_answer' => now(),
                    ]);
                }
            } catch (Exception $e){

            }
        }
    }

    public function finished()
    {
        $this->status = Job::STATUS_COMPLETED;
        $this->completed = now();
        $this->save();
        Notification::JobCompleted($this->id);
    }
/*
    public function theCrew()
    {
        $users = [];
        if($this->crew){
            $users = [];
            foreach($this->crew as $user_id=>$set){
                if($set){
                    $users[] = User::findOrFail($user_id);
                }
            }
            return $users;    
        } else {
            return [];
        }
    }

    public function crewNames()
    {
        $names = [];
        foreach ($this->theCrew() as $user){
            $names[] = $user->name;
        }
        return $names;
    }

    public function taskNames_old($ids=false)
    {
        $names = [];
        foreach ($this->tasks() as $task){
            $names[] = $task->name;
        }
        return $names;
    }

    public function taskTypes_old()
    {
        $names = [];
        foreach ($this->tasks as $task){
            $taskType = $task->taskType();
            if(!in_array($taskType,$names)){
                $names[] = $taskType;
            }
        }
        return $names;
    }*/

    public static function findJobReport($job_id, $task_id)
    {
        return Report::where('job_id',$job_id)->where('task_id',$task_id)->orderBy('updated_at','desc')->first();
    }

    public function findReport($task_id)
    {
        return self::findJobReport($this->id,$task_id);
    }

    public function taskReport($task_id)
    {
        $report = $this->findReport($task_id);
        if(!$report || !$report->media){
            return null;
        } else {
            return $report->media;
        }
    }

    public function sitePhoto()
    {
        foreach($this->tasks as $task){
            if(in_array($task->task_type,[Task::TYPE_PLANNED, Task::TYPE_PREVENTIVE, Task::TYPE_CORRECTIVE])){
                if($task->report_template){
                    $url = $task->report_template->frontPhotoUrl($this->id, 0);
                    if($url){
                        return $url;
                    }
                }
            }
        }
        return asset('images/frontpage.png');
    }

    public function vehiclePhoto()
    {
        foreach($this->tasks as $task){
            if($task->task_type == Task::TYPE_VEHICLE_MAINTENANCE){
                if($task->report_template){
                    $url = $task->report_template->frontPhotoUrl($this->id, 0);
                    if($url){
                        return $url;
                    }
                }
            }
        }
        return asset('images/frontpage.png');
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function site(){
        return $this->belongsTo(Site::class);
    }

    public function getTask($index=0){
        if(isset($this->tasks[$index])){
            return $this->tasks[$index];
        } else {
            return null;
        }
    }

    public function taskNames()
    {
        return $this->tasks->pluck('name')->toArray();
    }

    public function crewNames()
    {
        return $this->users->pluck('name')->toArray();
    }

    public function taskTypes()
    {
        $task_types = [];
        foreach($this->tasks->pluck('task_type')->toArray() as $task_type){
            $task_types[] = Task::taskTypeString($task_type);
        }
        return $task_types;
    }

    public function subscribed()
    {

    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class)->withPivot('task_order','report_data','started','completed','first_answer','last_answer')->orderByPivot('task_order','asc');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function userIds()
    {
        return $this->users->pluck('id')->toArray();
    }

    public function taskIds()
    {
//        return $this->tasks->pluck('id')->toArray();
        $task_ids = [];
        foreach($this->tasks as $task){
            $task_ids[$task->id] = ['task_order'=>$task->pivot->task_order];
        }
        return $task_ids;
    }

    public function vehicle(){
        return $this->belongsTo(Vehicle::class);
    }

    public function reports(){
        return $this->hasMany(Report::class);
    }

    public function apiCrew ()
    {
        $crew = [];
        foreach($this->users as $user){
            $crew[] = $user->apiUserInfo('Company',true);
        }
        return $crew;
    }

    public function apiTasks($multiple=true)
    {
        $tasks = [];
        foreach($this->tasks as $task){
            $tasks[] = $task->apiTaskInfo($task->pivot);
//            $tasks[] = ['id'=>$task->id,'name'=>$task->name,'order'=>$task->pivot->task_order,'started'=>$task->pivot->started,'completed'=>$task->pivot->completed];
        }
        if(empty($tasks)){
            return [];
        } else {
            if($multiple){
                return $tasks;
            } else {
                return $tasks[0];
            }
        }
    }

    public function apiJobInfo()
    {
        $lastAnswer = Answer::where('job_id',$this->id)->orderBy('updated_at','desc')->pluck('updated_at')->first();
        if($this->site){
            $site = ['id'=>$this->site->id,'identifier'=>$this->site->identifier,'country'=>$this->site->country->name,'island'=>$this->site->island,'name'=>$this->site->name];
        } else {
            $site = [];
        }
        if($this->customer){
            $customer = ['id'=>$this->customer_id,'name'=>$this->customer->name];
        } else {
            $customer = [];
        }

        return [
            'id'=>$this->id,
            'status'=>$this->apiStatus(),
            'order'=>$this->job_order,
            'customer'=>$customer,
            'site'=>$site,
            'vehicle'=>$this->vehicle?->apiVehicleInfo(),
            'assigned'=>$this->assigned,
            'crew'=>$this->apiCrew(),
            'task'=>$this->apiTasks(false),
            'tasks'=>$this->apiTasks(),
            'po_number'=>$this->po_number,
            'scope'=>$this->scope,
            'notes'=>$this->notes,
            'lastAnswer'=>$lastAnswer
        ];
    }

    public function apiJobTaskInfo($task)
    {
        $report = Report::where('job_id',$this->id)->where('task_id',$task->id)->orderby('updated_at','desc')->first();
        if($report){
            if($report->media){
                $pdf = $report->media->url; // route('report.task.download.pdf', [$this->id, $task->id]);
            } else {
                $pdf = null;
            }
        } else {
            $pdf = null;
        }

        return ['id'=>$task->id,'name'=>$task->name,'order'=>$task->pivot->task_order,'started'=>$task->pivot->started,'completed'=>$task->pivot->completed,'report'=>$pdf];
    //    return ['order'=>$task->pivot->task_order,'started'=>$task->pivot->started,'completed'=>$task->pivot->completed];
    }
}
