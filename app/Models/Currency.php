<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{

    public function apiCurrencyInfo()
    {
        return ['id'=>$this->id, 'symbol'=>$this->symbol,'name'=>$this->name];
    }
}
