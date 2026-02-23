<?php
namespace App\Models;

//use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Model;

class SublayoutPivot extends Model
{
    public $timestamps = false;
    protected $primaryKey = null;
    public $incrementing = false;
    
    protected $table = 'sublayouts';

    protected $casts = [
        'widths' => 'array',
    ];
   
    public static function getPivot($layout_id, $class, $id){
        return SublayoutPivot::where('layout_id', $layout_id)->where('sublayout_type', $class)->where('sublayout_id', $id)->first();
    }

    public function updateWidths($widths){
        SublayoutPivot::where('layout_id', $this->layout_id)->where('sublayout_type', $this->sublayout_type)->where('sublayout_id', $this->sublayout_id)->update(['widths'=>$widths]);
    }

    public function child()
    {
        $model = '\\' . $this->sublayout_type;
        return $model::findOrFail($this->sublayout_id);
    }

    public function layout()
    {
        return Layout::findOrFail($this->sublayout_id);
//        return $this->hasOne(Layout::class, 'sublayout_id');
    }

    public function field()
    {
        return Field::findOrFail($this->sublayout_id);
    //   return $this->hasOne(Field::class, 'sublayout_id');
    }

    public function special()
    {
        return SpecialLayout::findOrFail($this->sublayout_id);
    //   return $this->hasOne(Field::class, 'sublayout_id');
    }

}
