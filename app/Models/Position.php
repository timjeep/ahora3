<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Position extends Model
{
    const REASON_BACKGROUND     = 1;
    const REASON_DRIVING        = 2;
    const REASON_JOBSTARTED     = 3;
    const REASON_JOBCOMPLETED   = 4;
    const REASON_TASKSTARTED    = 5;
    const REASON_TASKCOMPLETED  = 6;

    public static $reasonStrings = [
        self::REASON_BACKGROUND     => 'Background',
        self::REASON_DRIVING        => 'Driving',
        self::REASON_JOBSTARTED     => 'Job Started',
        self::REASON_JOBCOMPLETED   => 'Job Completed',
        self::REASON_TASKSTARTED    => 'Task Started',
        self::REASON_TASKCOMPLETED  => 'Task Completed',
    ];

    public static function addLocation($reason, $latitude, $longitude, $job_id=null, $task_id=null, $vehicle_id=null, $user_id=null)
    {
        if(!$user_id){
            $user_id = Auth::id();
        }
        $position = new Position();
        $position->user_id = $job_id;
        $position->reason = $reason;
        $position->latitude = $latitude;
        $position->longitude = $longitude;
        $position->job_id = $job_id;
        $position->task_id = $task_id;
        $position->vehicle_id = $vehicle_id;
        $position->save();

        return $position;
    }
}