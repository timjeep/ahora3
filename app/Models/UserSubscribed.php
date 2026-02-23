<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserSubscribed extends Model
{
    protected $table = 'user_subscribed';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    public static function syncSubscriptions($user_id,$notification_types)
    {
        $user_notifications = UserSubscribed::where('user_id',$user_id)->pluck('notification_type')->toArray();

        foreach($user_notifications as $user_notification){
            if(!in_array($user_notification, $notification_types)){
                UserSubscribed::where('user_id',$user_id)->where('notification_type',$user_notification)->delete();
            }
        }
        foreach($notification_types as $notification_type){
            if(!in_array($notification_type,$user_notifications)){
                $userSubscribed = new UserSubscribed();
                $userSubscribed->user_id = $user_id;
                $userSubscribed->notification_type = $notification_type;
                $userSubscribed->save();
            }
        }
    }

    public static function subscribe($user_id,$notification_type)
    {
        $userSubscribed = UserSubscribed::where('user_id',$user_id)->where('notification_type',$notification_type)->get();
        if(!$userSubscribed){
            $userSubscribed = new UserSubscribed();
            $userSubscribed->user_id = $user_id;
            $userSubscribed->notification_type = $notification_type;
            $userSubscribed->save();
        }
    }

    public static function unsubscribe($user_id,$notification_type)
    {
        UserSubscribed::where('user_id',$user_id)->where('notification_type',$notification_type)->delete();
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
