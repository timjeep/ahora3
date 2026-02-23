<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function apiAddressInfo()
    {
        return['id'=>$this->id,'street1'=>$this->street1,'street2'=>$this->street2,'city'=>$this->city,'state'=>$this->state,'postal_code'=>$this->postal_code,'country'=>($this->country?$this->country->name:'')];
    }
}
