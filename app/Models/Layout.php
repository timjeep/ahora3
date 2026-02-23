<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Field;
use App\Models\SublayoutPivot;
use Illuminate\Support\Facades\Log;

class Layout extends Model
{
    public $timestamps = false;

    const TYPE_HEADER               = 1;
    const TYPE_FOOTER               = 2;
    const TYPE_SITE                 = 3;
    const TYPE_ANTENNA              = 4;
    const TYPE_FRONT                = 5;
    const TYPE_PORT                 = 6;
    const TYPE_RADIO                = 7;
    const TYPE_APPENDIX             = 8;
    const TYPE_VEHICLE_MAINTENANCE  = 9;
    const TYPE_PERSONAL_SAFETY      = 10;
    const TYPE_SITE_SAFETY          = 11;
    const TYPE_TOOLS                = 12;
    const TYPE_MICROWAVE            = 13;
    const TYPE_FWA                  = 14;

    public static $typeStrings = [
//        self::TYPE_HEADER               => 'Layout Header',
//        self::TYPE_FOOTER               => 'Layout Footer',
        self::TYPE_SITE                 => 'Layout Site',
        self::TYPE_ANTENNA              => 'Layout Antenna',
        self::TYPE_PORT                 => 'Layout Port',
        self::TYPE_RADIO                => 'Layout Radio',
        self::TYPE_APPENDIX             => 'Layout Appendix',
//        self::TYPE_FRONT                => 'Layout Front Page',
        self::TYPE_VEHICLE_MAINTENANCE  => 'Layout Vehicle Maintenance',
        self::TYPE_PERSONAL_SAFETY      => 'Layout Personal Safety',
        self::TYPE_SITE_SAFETY          => 'Layout Site Safety',
        self::TYPE_TOOLS                => 'Layout Tools',
        self::TYPE_MICROWAVE            => 'Layout Microave',
        self::TYPE_FWA                  => 'Layout FWA',
    ];

    public function type()
    {
        return isset(self::$typeStrings[$this->layout_type])?self::$typeStrings[$this->layout_type]:'Unknown';
    }

    public function formType()
    {
        switch ($this->layout_type){
            case self::TYPE_SITE:
                return Form::TYPE_SITE;
            case self::TYPE_ANTENNA:
                return Form::TYPE_ANTENNA;
            case self::TYPE_PORT:
                return Form::TYPE_PORT;
            case self::TYPE_RADIO:
                return Form::TYPE_RADIO;
            case self::TYPE_APPENDIX:
                return Form::TYPE_APPENDIX;
            case self::TYPE_VEHICLE_MAINTENANCE:
                return Form::TYPE_VEHICLE_MAINTENANCE;
            case self::TYPE_PERSONAL_SAFETY:
                return Form::TYPE_PERSONAL_SAFETY;
            case self::TYPE_SITE_SAFETY:
                return Form::TYPE_SITE_SAFETY;
            case self::TYPE_TOOLS:
                return Form::TYPE_TOOLS;
            case self::TYPE_MICROWAVE:
                return Form::TYPE_MICROWAVE;
            case self::TYPE_FWA:
                return Form::TYPE_FWA;
            default:
                Log::Alert('Company:'.config('company.id').' Layout::formType - Cannot map layout_type='.$this->layout_type);
                return null;
        }
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'attributes' => 'json',
    ];

    public function class(){
        return 'layout';
    }

    public function usedFieldList()
    {
        $fields = SublayoutPivot::where('layout_id',$this->id)->where('sublayout_type',Field::class)->pluck('sublayout_id')->toArray();
        foreach($this->layouts as $layout){
            $fields = array_merge($fields, $layout->usedFieldList());
        }
        return $fields;
    }

    public function unusedForms()
    {
        return [];
    }

    public function unusedFields($form_type)
    {
        return Field::where('company_id', config('company.id'))->whereNotIn('id',$this->usedFieldList())->whereIn('form_type',Form::compatibleForms($form_type))->get();
    }

    public function lastOrder()
    {
        $last = DB::table('sublayouts')->where('layout_id',$this->id)->orderBy('sublayout_order', 'desc')->first();
        if($last){
            return $last->sublayout_order;
        } else {
            return 0;
        }
    }

    public function findOrder($sublayout_type, $sublayout_id)
    {
        if($sublayout_id)
        {
            $pos = DB::table('sublayouts')->where('layout_id',$this->id)->where('sublayout_type',$sublayout_type)->where('sublayout_id',$sublayout_id)->first();
            return $pos->sublayout_order;    
        } else {
            return $this->lastOrder()+1;
        }
    }

    public function addObject($object_type, $object_id, $before_type, $before_id):void
    {
        // Figure out where we are inserting
        $order = $this->findOrder($before_type, $before_id);

        // Reorder everything after
        SublayoutPivot::where('layout_id',$this->id)->where('sublayout_order','>=',$order)->orderBy('sublayout_order', 'asc')->increment('sublayout_order');

        $sublayout = new SublayoutPivot();
        $sublayout->layout_id = $this->id;
        $sublayout->sublayout_type = $object_type;
        $sublayout->sublayout_id = $object_id;
        $sublayout->sublayout_order = $order;
        $sublayout->save();
    }

    public function addRow():void
    {
        $row = new Layout();
        $row->company_id = config('company.id');
        $row->layout_type = $this->layout_type;
        $row->main = false;
        $row->toc = false;
        $row->save();

        // Rows only add at the end.
        // Then move where you want them
        $order = $this->lastOrder()+1;

        $this->layouts()->attach($row->id, ['sublayout_order'=>$order]);

        // Rows always have at least one Column
        // You can only add Fields or Rows to Columns
        $row->addColumn();
    }

    public function addColumn():void
    {
        $column = new Layout();
        $column->company_id = config('company.id');
        $column->layout_type = $this->layout_type;
        $column->main = false;
        $column->toc = false;
        $column->save();

        // Columns only add at the end.
        // Then move where you want them.
        $order = $this->lastOrder()+1;

        $this->layouts()->attach($column->id, ['sublayout_order'=>$order]);
    }

    public function removeObject($object_class, $object_id):void
    {
        $object = $object_class::findOrFail($object_id);

        // First get where it is in order
        $order = $this->findOrder($object_class, $object_id);

        // Second get our pivot entry and delete it
        SublayoutPivot::where('layout_id',$this->id)->where('sublayout_type', $object_class)->where('sublayout_id', $object_id)->delete();

        // Then reorder the parent layout
        SublayoutPivot::where('layout_id',$this->id)->where('sublayout_order','>',$order)->orderBy('sublayout_order', 'asc')->decrement('sublayout_order');

        // The rest is only for layouts
        if ($object_class==Layout::class){
            // Delete any contents, columns may contain rows, fields, tables?, media?
            $children = SublayoutPivot::where('layout_id',$object_id)->get();
            foreach ($children as $child){
                $object->removeObject($child->sublayout_type, $child->sublayout_id);
            }

            // Finally delete the object only if it is a layout
            $object->delete();
        }
        
        // also delete if it is special
        if ($object_class==SpecialLayout::class){
            $object->delete();     
        }
    }

    public function moveObject($object_class, $object_id, $new_layout_id, $before_type, $before_id):void 
    {
        // First get where it is in order
        $order = $this->findOrder($object_class, $object_id);

        // Then reorder the layout
        SublayoutPivot::where('layout_id',$this->id)->where('sublayout_order','>',$order)->orderBy('sublayout_order', 'asc')->decrement('sublayout_order');

        // Find the order for the new layout
        $newLayout = Layout::findOrFail($new_layout_id);
        $order = $newLayout->findOrder($before_type, $before_id);

        // Then reorder the new layout
        SublayoutPivot::where('layout_id',$new_layout_id)->where('sublayout_order','>=',$order)->orderBy('sublayout_order', 'asc')->increment('sublayout_order');

        // Move it to the new layout
        SublayoutPivot::where('layout_id',$this->id)->where('sublayout_type', $object_class)->where('sublayout_id', $object_id)->update(['layout_id'=>$new_layout_id, 'sublayout_order'=>$order]);
    }

    public function rowEmpty($job_id, $task_id, $antenna_id=0, $port_id=0, $radio_id=0, $fwa_id=0)
    {
        foreach($this->layouts as $column){
            if(!$column->colEmpty($job_id, $task_id, $antenna_id, $port_id, $radio_id, $fwa_id=0)){
                return false;
            }
        }
        return true;
    }

    public function colEmpty($job_id, $task_id, $antenna_id=0, $port_id=0, $radio_id=0, $fwa_id=0){
        foreach($this->children() as $child){
            if($child instanceof Layout){
                if(!$child->rowEmpty($job_id, $task_id, $antenna_id, $port_id, $radio_id, $fwa_id)){
                    return false;
                }
            }elseif($child instanceof Field){
                if(!$child->isEmpty($job_id, $task_id, $antenna_id, $port_id, $radio_id, $fwa_id)){
                    return false;
                }
            }
        }
        return true;
    }


    public static function bodies()
    {
        return Layout::where('company_id',config('company.id'))->where('main',1)->whereIn('layout_type', [Layout::TYPE_SITE,Layout::TYPE_VEHICLE_MAINTENANCE,Layout::TYPE_PERSONAL_SAFETY,Layout::TYPE_SITE_SAFETY,Layout::TYPE_TOOLS])->get();
    }

    public static function antennas()
    {
        return Layout::where('company_id',config('company.id'))->where('main',1)->where('layout_type', Layout::TYPE_ANTENNA)->get();
    }

    public static function ports()
    {
        return Layout::where('company_id',config('company.id'))->where('main',1)->where('layout_type', Layout::TYPE_PORT)->get();
    }

    public static function radios()
    {
        return Layout::where('company_id',config('company.id'))->where('main',1)->where('layout_type', Layout::TYPE_RADIO)->get();
    }

    public static function microwaves()
    {
        return Layout::where('company_id',config('company.id'))->where('main',1)->where('layout_type', Layout::TYPE_MICROWAVE)->get();
    }

    public static function fwas()
    {
        return Layout::where('company_id',config('company.id'))->where('main',1)->where('layout_type', Layout::TYPE_FWA)->get();
    }

    public static function appendixes()
    {
        return Layout::where('company_id',config('company.id'))->where('main',1)->where('layout_type', Layout::TYPE_APPENDIX)->get();
    }

    public function body()
    {
        return $this->hasOne(Layout::class, 'site_layout_id');
    }

    public function layouts()
    {
        return $this->morphedByMany(Layout::class, 'sublayout')->orderByPivot('sublayout_order', 'asc');
    }
 
    /**
     * Get all of the videos that are assigned this tag.
     */
    public function fields()
    {
        return $this->morphedByMany(Field::class, 'sublayout')->orderByPivot('sublayout_order', 'asc');
    }

    public function layoutPivots()
    {
        return SublayoutPivot::where('layout_id',$this->id)->select('layout_id', 'sublayout_id', 'sublayout_type', 'sublayout_order' ,'widths')->orderBy('sublayout_order', 'asc')->get();
    }

    public function children()
    {
        $list = [];

        $pivots = $this->layoutPivots();

        foreach($pivots as $pivot)
        {
            $list[] = $pivot->child();
        }

        return $list;
    }
}