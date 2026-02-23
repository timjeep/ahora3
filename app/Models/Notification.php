<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Events\NotificationSent;

class Notification extends Model
{

    const NOTIFICATION_JOB_START    = 1;
    const NOTIFICATION_JOB_FINISH   = 2;
    const NOTIFICATION_TASK_START   = 3;
    const NOTIFICATION_TASK_FINISH  = 4;
    const NOTIFICATION_REPORT_READY = 5;

    static $notificationStrings = [
        self::NOTIFICATION_JOB_START    => 'Job Started',
        self::NOTIFICATION_JOB_FINISH   => 'Job Completed',
        self::NOTIFICATION_TASK_START   => 'Task Started',
        self::NOTIFICATION_TASK_FINISH  => 'Task Completed',
        self::NOTIFICATION_REPORT_READY => 'Report Ready',
    ];

    protected $fillable = [
        'read_at',
    ];

    public function notificationString()
    {
        return isset(self::$notificationStrings[$this->notification_type])?self::$notificationStrings[$this->notification_type]:'Unknown';
    }

    public static function JobStarted($job_id)
    {
        $notification = new Notification();
        $notification->company_id = config('company.id');
        $notification->job_id = $job_id;
        $notification->notification_type = Notification::NOTIFICATION_JOB_START;
        $notification->label = $notification->job->jobType().' Job Started'.(isset($notification->job->site)?(' at site '.$notification->job->site->identifier):'');
        $notification->save();

        $users = User::subscribed(self::NOTIFICATION_JOB_START);
        $user_ids = [];
        foreach($users as $user_id){
            $user = User::find($user_id);
            if($user && ($user->isCompanyEmployee() || $user->isCustomerEmployee($notification->job->customer_id))){
                $user_ids[] = $user_id;
            }
        }

        $notification->users()->sync($user_ids);

        broadcast(new NotificationSent($notification))->toOthers();
    }

    public static function JobCompleted($job_id)
    {
        $notification = new Notification();
        $notification->company_id = config('company.id');
        $notification->job_id = $job_id;
        $notification->notification_type = Notification::NOTIFICATION_JOB_FINISH;
        $notification->label = $notification->job->jobType().' Job Completed'.(isset($notification->job->site)?(' at site '.$notification->job->site->identifier):'');
        $notification->save();

        $users = User::subscribed(self::NOTIFICATION_JOB_START);
        $user_ids = [];
        foreach($users as $user_id){
            $user = User::find($user_id);
            if($user && ($user->isCompanyEmployee() || $user->isCustomerEmployee($notification->job->customer_id))){
                $user_ids[] = $user_id;
            }
        }

        $notification->users()->sync($user_ids);
        
        broadcast(new NotificationSent($notification))->toOthers();
    }

    public static function TaskStarted($job_id, $task_id)
    {
        $notification = new Notification();
        $notification->company_id = config('company.id');
        $notification->job_id = $job_id;
        $notification->task_id = $task_id;
        $notification->notification_type = Notification::NOTIFICATION_TASK_START;
        $notification->label = 'Task "'.$notification->task->name.'" Started'.(isset($notification->job->site)?(' at site '.$notification->job->site->identifier):'');
        $notification->save();

        $users = User::subscribed(self::NOTIFICATION_TASK_START);
        $user_ids = [];
        foreach($users as $user_id){
            $user = User::find($user_id);
            if($user && ($user->isCompanyEmployee() || $user->isCustomerEmployee($notification->job->customer_id))){
                $user_ids[] = $user_id;
            }
        }

        $notification->users()->sync($user_ids);
        
        broadcast(new NotificationSent($notification))->toOthers();
    }

    public static function TaskCompleted($job_id, $task_id)
    {
        $notification = new Notification();
        $notification->company_id = config('company.id');
        $notification->job_id = $job_id;
        $notification->task_id = $task_id;
        $notification->notification_type = Notification::NOTIFICATION_TASK_FINISH;
        $notification->label = 'Task "'.$notification->task->name.'" Completed'.(isset($notification->job->site)?(' at site '.$notification->job->site->identifier):'');
        $notification->save();

        $users = User::subscribed(self::NOTIFICATION_TASK_FINISH);
        $user_ids = [];
        foreach($users as $user_id){
            $user = User::find($user_id);
            if($user && ($user->isCompanyEmployee() || $user->isCustomerEmployee($notification->job->customer_id))){
                $user_ids[] = $user_id;
            }
        }

        $notification->users()->sync($user_ids);

        broadcast(new NotificationSent($notification))->toOthers();
    }

    public static function ReportReady(int $company_id, int $job_id, int $task_id, int $report_id, bool $customer=false)
    {
        $report = Report::find($report_id);
        $job = Job::find($job_id);

        $notification = new Notification();
        $notification->company_id = $company_id;
        $notification->job_id = $job_id;
        $notification->task_id = $task_id;
        $notification->source_id = $report_id;
        $notification->notification_type = Notification::NOTIFICATION_REPORT_READY;
        $notification->label = Notification::$notificationStrings[$notification->notification_type].' for Task: '.(isset($report->task)?$report->task->name:'Unknown') . (isset($job->site)?(' at site: '.$job->site->identifier):'');
        $notification->save();

        $users = User::subscribed(self::NOTIFICATION_REPORT_READY);
        $user_ids = [];
        foreach($users as $user_id){
            $user = User::find($user_id);
            if($customer){
                if($user && $user->isCustomerEmployee($notification->job->customer_id)){
                    $user_ids[] = $user_id;
                }
            } else {
                if($user && $user->isCompanyEmployee($notification->job->company_id)){
                    $user_ids[] = $user_id;
                }
            }
        }

        $notification->users()->syncWithoutDetaching($user_ids);
        
        broadcast(new NotificationSent($notification))->toOthers();
    }

    public static function mine($unread = true)
    {
        $query = Auth::user()->notifications;
        if($unread){
            $query->wherePivotNull('read_at');
        }

        return $query->get();
    }

    public function subscribed()
    {
        return $this->belongsToMany(User::class, 'user_subscribed','notification_type');
    }

    public function job()
    {
        return $this->belongsTo(Job::class,'job_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class,'task_id');
    }

    public function report()
    {
        return $this->belongsTo(Report::class,'source_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class,'company_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('read_at');
    }

    public function apiNotificationInfo()
    {
        $read = false;
        foreach($this->users as $user){
            if($user->id==Auth::id()){
                $read = $user->pivot->read_at?$user->pivot->read_at:false;
                break;
            }
        }
        return ['id'=>$this->id, 'label'=>$this->label, 'read'=>$read, 'created'=>$this->created_at];
    }
}
