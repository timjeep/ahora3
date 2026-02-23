<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Exception;  
use App\Pdfs\CompanyInvoicePdf;

class Invoice extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
    ];

    public function invoiceNumber($prefix=null)
    {
        $year = (new Carbon($this->from_date))->format('y');   
        return ($prefix?($prefix.'-'):'') . sprintf('%02d%06d',$year,$this->id);
    }

    public static function thisMonthBillable ($company_id, $from_date, $to_date)
    {
        $jobs = Job::where('company_id',$company_id)->where('billable',1)->whereNull('invoice_id')->whereNotNull('first_answer')->whereRaw('DATE(completed) >= "'.$from_date->format('Y-m-d').'"')->whereRaw('DATE(completed) <= "'.$to_date->format('Y-m-d').'"')->get();
   
        return $jobs->count();
    }

    public static function create($company_id, $from_date, $to_date,$prefix=null)
    {
        $query = Job::where('company_id',$company_id)->where('billable',1)->whereNull('invoice_id')->whereNotNull('first_answer')->whereRaw('DATE(completed) >= "'.$from_date->format('Y-m-d').'"')->whereRaw('DATE(completed) <= "'.$to_date->format('Y-m-d').'"');
        $sql = $query->toSql();
        $jobs = $query->get();
        if ($jobs->isEmpty()){
        //    return [false,false,false];   // Always charge the base rate now 24 Jan 2025
        }

        $invoice = new Invoice();
        $invoice->company_id = $company_id;
        $invoice->from_date = $from_date->format('Y-m-d');
        $invoice->to_date = $to_date->format('Y-m-d');
        $invoice->job_count = count($jobs);
        $invoice->save();
        $invoice->invoice_number = $invoice->invoiceNumber($prefix);
        $invoice->save();

        $num_tasks = 0;
        foreach($jobs as $job){
            $job->invoice_id = $invoice->id;
            $num_tasks += count($job->tasks);
            $job->save();
        }

        $link = $invoice->generateStripePaymentLink($num_tasks);

        $pdf = CompanyInvoicePdf::generateInvoice($invoice, $link);

        try {
            $media = new Media();
            $media->company_id = $company_id;
            $media->put_invoice_pdf($invoice->id,$pdf);
            
            $invoice->media_id = $media->id;
            $invoice->save();
        } catch (Exception $e){
            Log::error('Cron: Invoice::create - Failed to save invoice for invoice_id='.$invoice->id.', error='.$e->getMessage());
            $invoice->delete();
            return [false,false,false];
        }

        return [$invoice,$pdf,$link];
    }

    public function generateStripePaymentLink($num_tasks)
    {

        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            // Get the product
            $base = $stripe->products->retrieve(config('services.stripe.app_base'), []);
            $product = $stripe->products->retrieve(config('services.stripe.product'), []);

            $line_items = [[
                'price' => $base->default_price,
                'quantity' => 1,
            ]];

            if($num_tasks){
                $line_items [] = [
                    'price' => $product->default_price,
                    'quantity' => $num_tasks,
                ];
            }

            $link = $stripe->paymentLinks->create([
                'line_items' => $line_items,
                'currency' => 'USD',
            ]);
            return $link->url;
        } catch (Exception $e) {
            Log::Error('Invoice::generateStripePaymentLink -- Unable to create link error=' . $e->getMessage());
            return null;
        }
    }

    public static function generatePDF(int $invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);

        return CompanyInvoicePdf::generateInvoice($invoice);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}