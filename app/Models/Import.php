<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{

    protected $casts = [
        'mapping' => 'json',
    ];

    public static function getMapping($company_id, $customer_id, $country_id, $generation)
    {
        $import = Import::where('company_id',$company_id)->where('customer_id', $customer_id)->where('country_id', $country_id)->where('generation', $generation)->orderBy('created_at', 'desc')->first();
        if(empty($import)){
            return [];
        }

        if(empty($import->mapping)){
            return [];
        }

        return $import->mapping;
    }

    public static function imported ($company_id, $customer_id, $country_id, $generation, $filename, $mapping)
    {
        $import = new Import();

        $import->company_id = $company_id;
        $import->customer_id = $customer_id;
        $import->country_id = $country_id;
        $import->generation = $generation;
        $import->filename = $filename;
        $import->mapping = $mapping;

        $import->save();

        return $import;
    }

    public function added($antenna_models, $sites, $antennas, $ports)
    {
        $this->antenna_models_added = $antenna_models;
        $this->sites_added = $sites;
        $this->antennas_added = $antennas;
        $this->ports_added = $ports;
        $this->save();
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }

    public function company(){
        return $this->belongsTo(Company::class);
    }

    public function customer(){
        return $this->belongsTo(Customer::class);
    }
}