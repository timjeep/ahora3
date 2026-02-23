<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Job;
use App\Models\Media;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class Report extends Model
{
    const STATUS_GENERATED  = 1;
    const STATUS_APPROVED   = 2;
    const STATUS_REFUSED    = 3;

    public static $statusStrings = [
        self::STATUS_GENERATED  => 'Waiting Approval',
        self::STATUS_APPROVED   => 'Approved',
        self::STATUS_REFUSED    => 'Refused',
    ];
    public static function generatePdf($job_id, $task_id)
    {
        ini_set('memory_limit', '512M');

        $job = Job::find($job_id);
        if(!$job){
            abort(404);
        }
        $task = Task::find($task_id);
        if(!$task){
            abort(404);
        }

        if(isset(ReportTemplate::$reportClasses[$task->report_template->report_class])){
            $reportClass = ReportTemplate::$reportClasses[$task->report_template->report_class];
        } else {
            Log::error('Report::generatePdf - ReportClass not set for task='.$task_id);
            $reportClass = ReportTemplate::$reportClasses[ReportTemplate::REPORT_BASE];
        }

        $pdf = new $reportClass('P', 'mm', 'LETTER', true, 'UTF-8', true);
        $pdf->generatePDF($job, $task);
        return $pdf;
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}