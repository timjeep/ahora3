<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'currency_id',
    ];

    public static function getName($country_id){
        return Country::where('id', $country_id)->pluck('name')->first();
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

/*    public function flag()
    {
        return $this->belongsTo(Media::class, 'flag_id');
    }*/

    public function taxRate()
    {
        return TaxRate::rate($this->id);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
