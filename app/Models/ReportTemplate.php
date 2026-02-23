<?php
namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Pdfs\Report2Pdf;
use App\Pdfs\VehicleReportPdf;
use App\Pdfs\MicrowaveReportPdf;
use App\Pdfs\ToolReportPdf;
use App\Pdfs\BatteryReportPdf;
use App\Pdfs\EricssonReportPdf;
use App\Pdfs\FlowInspectionReportPdf;
use Exception;  

class ReportTemplate extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'options'=>'json',
        'antenna'=>'json',
    ];

    const REPORT_BASE       = 0;
    const REPORT_VEHICLE    = 1;
    const REPORT_MICROWAVE  = 2;
    const REPORT_TOOLS      = 3;
    const REPORT_BATTERY    = 4;
    const REPORT_ERICSSON   = 5;
    const REPORT_FLOW_INSPECTION = 6;

    static $reportStrings = [
        self::REPORT_BASE       => 'Base',
        self::REPORT_VEHICLE    => 'Vehicle',
        self::REPORT_MICROWAVE  => 'Microwave',
        self::REPORT_TOOLS      => 'Tools',
        self::REPORT_BATTERY    => 'Battery',
        self::REPORT_ERICSSON   => 'Ericsson',
        self::REPORT_FLOW_INSPECTION => 'Flow Inspection',
    ];

    static $reportClasses = [
        self::REPORT_BASE       => Report2Pdf::class,
        self::REPORT_VEHICLE    => VehicleReportPdf::class,
        self::REPORT_MICROWAVE  => MicrowaveReportPdf::class,
        self::REPORT_TOOLS      => ToolReportPdf::class,
        self::REPORT_BATTERY    => BatteryReportPdf::class,
        self::REPORT_ERICSSON   => EricssonReportPdf::class,
        self::REPORT_FLOW_INSPECTION => FlowInspectionReportPdf::class,
    ];

    public function reportClass()
    {
        return isset(self::$reportClasses[$this->report_class])?self::$reportClasses[$this->report_class]:null;
    }

    public function antennaType($job_id, $task_id)
    {
        $field_id = $this->antennaField('field_type');
        if(empty($field_id)){
            return null;
        }

        $answer = Answer::findAnswer($job_id,$task_id,$field_id,0,0,0,0,0);
        if(!$answer){
            return null;
        }

        return $answer->value;
    }

    public function antennaField($fieldname)
    {
        return isset($this->antenna[$fieldname])?$this->antenna[$fieldname]:null;
    }

    public function fieldAnswer($job_id, $task_id, $fieldname, $customer)
    {
        $field_id = $this->antennaField($fieldname);
        if(!$field_id){
            Log::alert('ReportTemplate::fieldAnswer - No Field set for fieldname='.$fieldname);
            return null;
        }
        $field = Field::find($field_id);
        if(empty($field)){
            Log::alert('ReportTemplate::fieldAnswer - No Field for fieldname='.$fieldname.', field_id='.$field_id);
            return null;
        }
        $answer = Answer::findAnswer($job_id,$task_id,$field_id,0,0,0,0,0);
        if(empty($answer)){
            return null;
        }
        return $field->value($answer,$customer);
    }

    public function frontPhoto($job_id, $task_id, &$width, &$height,$default='images/frontpage.png')
    {
        if($this->front_photo_field_id){
            $answer = Answer::findAnswer($job_id,$task_id,$this->front_photo_field_id,0,0,0,0,0,0);
            if($answer){
                try{
                    $mediaList = json_decode($answer->value);
                    $media_id = $mediaList[0];
                } catch (Exception $e){
                    $media_id = intval($answer->value);
                }
                if($media_id){
                    $media = Media::find($media_id);
                    if($media){
                     //   return '@'.file_get_contents($media->url);
                    //    return Storage::disk('s3')->get($media->path);
                        $media->get_from_s3();
                        $width = imagesx($media->getImage());
                        $height = imagesy($media->getImage());
                        ob_start();
                        imagepng($media->getImage());
                        return '@'.ob_get_clean();
                       // return $media->image(Media::MEDIASCALE_800);
                    }
                }
            }
        }

        return storage_path($default);
    }

    public function frontPhotoUrl($job_id, $task_id=0)
    {
        if($this->front_photo_field_id){
            $answer = Answer::findAnswer($job_id,$task_id,$this->front_photo_field_id,0,0,0,0,0,0);
            if($answer){
                try{
                    $mediaList = json_decode($answer->value);
                    $media_id = $mediaList[0];
                } catch (Exception $e){
                    $media_id = intval($answer->value);
                }
                if($media_id){
                    $media = Media::find($media_id);
                    if($media){
                        return $media->url;
                    }
                }
            }
        }
        return null;
    }

    public function nextServiceDate($job_id, $task_id=0)
    {
        if($this->next_service_field_id){
            $answer = Answer::findAnswer($job_id,$task_id,$this->next_service_field_id,0,0,0,0,0,0);
            if($answer){
                return $answer->value;
            }
        }
        return null;
    }

    public function licenceExpiryDate($job_id, $task_id=0)
    {
        if($this->licence_expiry_field_id){
            $answer = Answer::findAnswer($job_id,$task_id,$this->licence_expiry_field_id,0,0,0,0,0,0);
            if($answer){
                return $answer->value;
            }
        }
        return null;
    }

    public function KMs($job_id, $task_id=0)
    {
        if($this->kms_field_id){
            $answer = Answer::findAnswer($job_id,$task_id,$this->kms_field_id,0,0,0,0,0,0);
            if($answer){
                return $answer->value;
            }
        }
        return null;
    }

    public function antennaShow()
    {
        return isset($this->antenna['show']) && $this->antenna['show'];
    }

    public function frontPhotoField()
    {
        return $this->belongsTo(Field::class, 'front_photo_field_id');
    }

    public function site_layout()
    {
        return $this->belongsTo(Layout::class, 'site_layout_id');
    }

    public function antenna_layout()
    {
        return $this->belongsTo(Layout::class, 'antenna_layout_id');
    }

    public function port_layout()
    {
        return $this->belongsTo(Layout::class, 'port_layout_id');
    }

    public function radio_layout()
    {
        return $this->belongsTo(Layout::class, 'radio_layout_id');
    }

    public function microwave_layout()
    {
        return $this->belongsTo(Layout::class, 'microwave_layout_id');
    }

    public function fwa_layout()
    {
        return $this->belongsTo(Layout::class, 'fwa_layout_id');
    }

    public function appendix_layout()
    {
        return $this->belongsTo(Layout::class, 'appendix_layout_id');
    }
}
