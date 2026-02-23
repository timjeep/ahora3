<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Issue extends Model
{
    use SoftDeletes;

    const STATUS_NONE       = 0;
    const STATUS_CREATED    = 1;
    const STATUS_ASSIGNED   = 2;
    const STATUS_INPROGRESS = 3;
    const STATUS_COMPLETED  = 4;

    public static $statusStrings = [
        self::STATUS_NONE       => 'None',
        self::STATUS_CREATED    => 'Created',
        self::STATUS_ASSIGNED   => 'Assigned',
        self::STATUS_INPROGRESS => 'In Progress',
        self::STATUS_COMPLETED  => 'Completed',
    ];

    public static $activeStatus = [self::STATUS_ASSIGNED, self::STATUS_INPROGRESS];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'status',
    ];

    public function getStatus(){
        return isset(self::$statusStrings[$this->status])?self::$statusStrings[$this->status]:'Unknown';
    }

}
