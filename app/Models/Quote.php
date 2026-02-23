<?php
namespace App\Models;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Pdfs\CustomerQuotePdf;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Quote extends Model
{
    use SoftDeletes;

    const STATUS_DRAFT      = 1;
    const STATUS_CREATED    = 2;
    const STATUS_SENT       = 3;
    const STATUS_JOB        = 4;
    const STATUS_COMPLETED  = 5;
    const STATUS_CANCELLED  = 6;

    public static $statusStrings = [
        self::STATUS_DRAFT      => 'Draft',
        self::STATUS_CREATED    => 'Created',
        self::STATUS_SENT       => 'Sent',
        self::STATUS_JOB        => 'Job Created',
        self::STATUS_COMPLETED  => 'Completed',
        self::STATUS_CANCELLED  => 'Cancelled',
    ];

    public static $statusApiStrings = [
        self::STATUS_DRAFT      => 'draft',
        self::STATUS_CREATED    => 'created',
        self::STATUS_SENT       => 'sent',
        self::STATUS_JOB        => 'job-created',
        self::STATUS_COMPLETED  => 'completed',
        self::STATUS_CANCELLED  => 'cancelled',
    ];

    public static $customerStatusStrings = [
        self::STATUS_SENT       => 'Received',
        self::STATUS_JOB        => 'Job Created',
        self::STATUS_COMPLETED  => 'Completed',
        self::STATUS_CANCELLED  => 'Cancelled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'details' => 'json',
    ];

    public static function theStatus($status){
        return isset(self::$statusStrings[$status])?self::$statusStrings[$status]:'Unknown';
    }

    public function getStatus(){
        return self::theStatus($this->status);
    }

    public function getApiStatus(){
        return isset(self::$statusApiStrings[$this->status])?self::$statusApiStrings[$this->status]:'unknown';
    }

    public static function generatePdf($quote_id)
    {
        return CustomerQuotePdf::generateQuote($quote_id);
    }

    public function calculateQuoteNumber($customer_id=null){
        if($customer_id){
            $customer = Customer::find($customer_id);
        }elseif($this->customer){
            $customer = $this->customer;
        }else{
            $customer=null;
        }
        if($customer){
            if($this->id){
                return $this->quote_number;
            } else {
                $now = Carbon::now();
                return substr($now->format('Ymd'),1).'-'.Str::upper($customer->slug) .'-'.$now->format('y').sprintf('%03d',Quote::where('created','like',($now->format('Y').'%'))->count() + 1);
            }
        }
        return substr(Carbon::now()->format('Ymd'),1).'-';
    }

    public function calculateTotals()
    {
        if($this->customer){
            if($this->customer->country){
                $taxrate = $this->customer->country->taxRate();
            } else {
                $taxrate = 0;
            }
        } else {
            $taxrate = 0;
        }

        $subtotal = 0;
        $details = [];
        if(is_array($this->details)){
            foreach ($this->details as $item_idx=>$item){
                $lineTotal =(isset($item['quantity'])?$item['quantity']:0) * (isset($item['price'])?$item['price']:0);
                $details[$item_idx] = $item;
                $details[$item_idx]['total'] = round($lineTotal,2);
                $subtotal += $lineTotal;
            }
        }
        $this->details = $details;
        $this->subtotal = round($subtotal,2);
        $this->vat = round($this->subtotal * $taxrate / 100,2);
        $this->total = $this->subtotal + $this->vat;
    }

    public function create(){
        try {
            $pdf = CustomerQuotePdf::generateQuote($this->id);
        } catch (Exception $e){
            Log::error('Company:'.config('company.id'). ' CompanyQuoteTable::createQuote - Failed to generate pdf for quote_id='.$this->id.', error='.$e->getMessage());
        }

        try {
            $media = new Media();
            $media->company_id = config('company.id');
            $media->put_quote_pdf($this->id,$pdf);
            
        } catch (Exception $e){
            Log::error('Company:'.config('company.id'). ' CompanyQuoteTable::createQuote - Failed to save pdf for quote_id='.$this->id.', error='.$e->getMessage());
        }

        $this->status = Quote::STATUS_CREATED;
        $this->media_id = $media->id;
        $this->save();
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function site(){
        return $this->belongsTo(Site::class);
    }

    public function job(){
        return $this->belongsTo(Job::class);
    }

    public function currency(){
        return $this->belongsTo(Currency::class);
    }

    public function media(){
        return $this->belongsTo(Media::class);
    }
}
