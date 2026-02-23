<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
//use Illuminate\Database\Eloquent\Model;

class CustomerSite extends Pivot
{
    //public $timestamps = false;
    //protected $primaryKey = null;
    //public $incrementing = false;
    
    protected $table = 'customer_site';

/*    protected $casts = [
    ];*/
   
    const OWNER_MAIN        = 1;
    const OWNER_SECONDARY   = 2;

    public static $ownerStrings = [
        self::OWNER_MAIN        => 'Owner',
        self::OWNER_SECONDARY   => 'Secondary',
    ];


}
