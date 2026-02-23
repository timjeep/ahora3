<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\User;
use App\Models\Company;
use Carbon\Carbon;
use GdImage;
use PDO;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use function Livewire\str;

use function GuzzleHttp\Promise\exception_for;

class Media extends Model {

    use SoftDeletes;
    
    const MEDIATYPE_NONE    = 0;
    const MEDIATYPE_IMAGE   = 1;
    const MEDIATYPE_VIDEO   = 2;
    const MEDIATYPE_PDF     = 3;
    const MEDIATYPE_XLS     = 4;
    const MEDIATYPE_DOC     = 5;

    const MEDIAUSE_NONE            = 0;
    const MEDIAUSE_AVATAR          = 1;
    const MEDIAUSE_FIELD           = 2;
    const MEDIAUSE_COMPANYLOGO     = 3;
    const MEDIAUSE_CUSTOMERLOGO    = 4;
    const MEDIAUSE_LAYOUT          = 5;
    const MEDIAUSE_REPORT          = 6;
    const MEDIAUSE_INVOICE         = 7;
    const MEDIAUSE_LETTERHEAD      = 8;
    const MEDIAUSE_QUOTE           = 9;

    const MEDIAUSE_ANSWER          = 11;
    
    const MEDIASCALE_ORIGINAL   = 0;
    const MEDIASCALE_100        = 1;
    const MEDIASCALE_200        = 2;
    const MEDIASCALE_400        = 3;
    const MEDIASCALE_800        = 4;
     
    public static $filetype_strings = [
        self::MEDIATYPE_NONE    => 'None',
        self::MEDIATYPE_IMAGE   => 'Image',
        self::MEDIATYPE_VIDEO   => 'Video',
        self::MEDIATYPE_PDF     => 'PDF',
        self::MEDIATYPE_XLS     => 'Excel',
        self::MEDIATYPE_DOC     => 'Word',
    ];
    
    public static $fileuse_strings = [
        self::MEDIAUSE_NONE            => 'None',
        self::MEDIAUSE_AVATAR          => 'Avatar',
        self::MEDIAUSE_FIELD           => 'Field',
        self::MEDIAUSE_COMPANYLOGO     => 'Company Logo',
        self::MEDIAUSE_CUSTOMERLOGO    => 'Customer Logo',
        self::MEDIAUSE_LAYOUT          => 'Layout',
        self::MEDIAUSE_REPORT          => 'Report',
        self::MEDIAUSE_INVOICE         => 'Invoice',
        self::MEDIAUSE_LETTERHEAD      => 'Letterhead',
        self::MEDIAUSE_QUOTE           => 'Quote',

        self::MEDIAUSE_ANSWER          => 'Answer',
    ];
    
    public static $filescale_strings = [
        self::MEDIASCALE_ORIGINAL   => 'Original',
        self::MEDIASCALE_100        => '100px',
        self::MEDIASCALE_200        => '200px',
        self::MEDIASCALE_400        => '400px',
        self::MEDIASCALE_800        => '800px',
    ];
    
    public static $filescale_data = [
        self::MEDIASCALE_ORIGINAL   => ['field'=>'url',    'scale'=>false,  'ext'=>'',      'stroke'=>3,    'font'=>3],
        self::MEDIASCALE_100        => ['field'=>'px100',  'scale'=>100,    'ext'=>'-100',  'stroke'=>1,    'font'=>0.5],
        self::MEDIASCALE_200        => ['field'=>'px200',  'scale'=>200,    'ext'=>'-200',  'stroke'=>2,    'font'=>1],
        self::MEDIASCALE_400        => ['field'=>'px400',  'scale'=>400,    'ext'=>'-400',  'stroke'=>3,    'font'=>2],
        self::MEDIASCALE_800        => ['field'=>'px800',  'scale'=>800,    'ext'=>'-800',  'stroke'=>3,    'font'=>3],
    ];

    const RESIZE_NONE = 0;
    const RESIZE_INPROGRESS = 1;
    const RESIZE_ERROR = 2;

    public static $resize_strings = [
        self::RESIZE_NONE       => 'None',
        self::RESIZE_INPROGRESS => 'In Progress',
        self::RESIZE_ERROR      => 'Error',
    ];

    const S3STATUS_UNKNOWN      = 0;
    const S3STATUS_UNAVAILABLE  = 1;
    const S3STATUS_AVAILABLE    = 2;
    const S3STATUS_PENDING      = 3;

    public static $s3status_strings = [
        self::S3STATUS_UNKNOWN      => 'Unknown',
        self::S3STATUS_UNAVAILABLE  => 'Unavailable',
        self::S3STATUS_AVAILABLE    => 'Available',
        self::S3STATUS_PENDING      => 'Pending',
    ];

    public static $fileformats = ['png', 'webp','jpeg'];

    protected $casts = [
        'metadata' => 'json',
        'markup' => 'json',
    ];
     
    private $image = null;
    private $scaled = 1;

    public function mediaUsage(){
        return isset(self::$fileuse_strings[$this->media_use])?self::$fileuse_strings[$this->media_use]:'Unknown';
    }
    
    public function mediaType(){
        return isset(self::$filetype_strings[$this->media_type])?self::$filetype_strings[$this->media_type]:'Unknown';
    }

    public function s3StatusString(){
        return isset(self::$s3status_strings[$this->s3status])?self::$s3status_strings[$this->s3status]:self::$s3status_strings[self::S3STATUS_UNKNOWN];
    }
     
    public static function changeExt ($name, $ext, $scale='')
    {
        return substr($name, 0, strrpos($name, '.')). $scale .'.'. $ext;
    }

    public function name()
    {
        switch($this->media_use){

            case self::MEDIAUSE_FIELD:
                return Field::findOrFail($this->media_use_id)->name;

            case self::MEDIAUSE_COMPANYLOGO:
                return Company::findOrFail($this->media_use_id)->name;
    
            case self::MEDIAUSE_CUSTOMERLOGO:
                return Customer::findOrFail($this->media_use_id)->name;

            case self::MEDIAUSE_AVATAR:
                return User::findOrFail($this->media_use_id)->name;
        
            case self::MEDIAUSE_LAYOUT:
                return Layout::findOrFail($this->media_use_id)->name;



            case self::MEDIAUSE_ANSWER:
                $answer = Answer::findOrFail($this->media_use_id);
                if($answer){
                    return $answer->field->name;
                }
    
            default:
                Log::alert('Company:'.config('company.id').' Media::name - Unknown media_use ='.$this->media_use);
                return 'Unknown';
        }
    }
    
    public function image ($scale, $format=null, $timestamp=true)
    {
        if (empty($this->url)) {
            return null;
        }
        $scaledata = Media::$filescale_data[$scale];
        $field = $scaledata['field'];
        
        if (($scale != Media::MEDIASCALE_ORIGINAL) && $this->$field)
        {
            if($format){
                $image = Media::changeExt($this->url, 'png', $scaledata['ext']);
            } else {
                if (isset($_SERVER['HTTP_ACCEPT']) && (stripos($_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false))
                {
                    $image =  Media::changeExt($this->url, 'webp', $scaledata['ext']);
                } else {
                    $image =  Media::changeExt($this->url, 'png', $scaledata['ext']);
                }
            }
            if($timestamp){
                return $image .'?timestamp='.strtotime($this->updated_at);
            } else {
                return $image;
            }
        } else {
            return $this->url;
        }
    }

    public function icon($scale=self::MEDIASCALE_100)
    {
        switch($this->media_type){

            case self::MEDIATYPE_IMAGE:
                return $this->image($scale,'png',true);

            case self::MEDIATYPE_PDF:
                return asset('images/pdf.png');

            default:
                return asset('images/file.png');
        }
    }

    private function delete_s3()
    {
        Storage::disk('s3')->delete($this->path);
        foreach(self::$filescale_data as $scaledata){
            if($scaledata['scale'] && $this->{$scaledata['field']}){
                foreach(self::$fileformats as $format){
                    $path = Media::changeExt($this->path, $format, $scaledata['ext']);
                    Storage::disk('s3')->delete($path);
                }
            }
        }
    }
     
    public static function delete_file ($media_id) {
        $media = Media::find($media_id);
        if (!empty($media)) {
            $media->deleteMedia();
        }
    }

    public function regenerateMedia()
    {
        foreach (Media::$filescale_data as $scaledata){
            if($scaledata['scale']){
                $this->{$scaledata['field']} = false;
            }
        }
        $this->save();
    }

    public function deleteMedia()
    {
        //$this->delete_s3();
        $this->delete();   
    }
     
    public static function find_file ($filename) {
        return Media::where('name', $filename)->get();
    }
     
    public static function file_exists ($filename, $notfile_id=0) {
        return (Media::where('name', $filename)->where('id', '!=', $notfile_id)->count() > 0);
    }

    private function gps2Num($coordPart){
        $parts = explode('/', $coordPart);
        if(count($parts) <= 0)
        return 0;
        if(count($parts) == 1)
        return $parts[0];
        return floatval($parts[0]) / floatval($parts[1]);
    }

     public function get_image_location($exif)
     {
        if($exif && isset($exif['GPS']) && isset($exif['GPS']['GPSLatitudeRef']) && isset($exif['GPS']['GPSLatitude']) && isset($exif['GPS']['GPSLongitudeRef']) && isset($exif['GPS']['GPSLongitude'])){
            $GPSLatitudeRef = $exif['GPS']['GPSLatitudeRef'];
            $GPSLatitude    = $exif['GPS']['GPSLatitude'];
            $GPSLongitudeRef= $exif['GPS']['GPSLongitudeRef'];
            $GPSLongitude   = $exif['GPS']['GPSLongitude'];
            
            $lat_degrees = count($GPSLatitude) > 0 ? $this->gps2Num($GPSLatitude[0]) : 0;
            $lat_minutes = count($GPSLatitude) > 1 ? $this->gps2Num($GPSLatitude[1]) : 0;
            $lat_seconds = count($GPSLatitude) > 2 ? $this->gps2Num($GPSLatitude[2]) : 0;
            
            $lon_degrees = count($GPSLongitude) > 0 ? $this->gps2Num($GPSLongitude[0]) : 0;
            $lon_minutes = count($GPSLongitude) > 1 ? $this->gps2Num($GPSLongitude[1]) : 0;
            $lon_seconds = count($GPSLongitude) > 2 ? $this->gps2Num($GPSLongitude[2]) : 0;
            
            $lat_direction = ($GPSLatitudeRef == 'W' or $GPSLatitudeRef == 'S') ? -1 : 1;
            $lon_direction = ($GPSLongitudeRef == 'W' or $GPSLongitudeRef == 'S') ? -1 : 1;
            
            $latitude = $lat_direction * ($lat_degrees + ($lat_minutes / 60) + ($lat_seconds / (60*60)));
            $longitude = $lon_direction * ($lon_degrees + ($lon_minutes / 60) + ($lon_seconds / (60*60)));
    
            return array('lat'=>round($latitude,9), 'lng'=>round($longitude,9));
        }else{
            return false;
        }
    }

    public function get_image_datetime($exif)
    {
        try {
            if (isset($exif['IFDO']['DateTime'])){
                return Carbon::createFromTimeString($exif['IFDO']['DateTime'])->toDateTimeString();
            }

            if (isset($exif['EXIF']['DateTimeOriginal'])){
                return Carbon::createFromTimeString($exif['EXIF']['DateTimeOriginal'])->toDateTimeString();
            }

            // This only gets the timestamp of the newly downloaded file which is .. now
//            if (isset($exif['FILE']['FileDateTime'])){
//                return Carbon::createFromTimestamp($exif['FILE']['FileDateTime'])->toDateTimeString();
//            }
        } catch (Exception $e){
            Log::alert('Company:'.config('company.id').' Media::get_image_datetime - unable to parse datetime for media_id='.$this->id);
            return false;
        }
        return false;
    }

    public static function generateHashNameWithOriginalNameEmbedded($filename, $extension)
    {
        // Taken from Livewire
        $hash = str()->random(30);
        $meta = str('-meta'.base64_encode($filename).'-')->replace('/', '_');

        return $hash.$meta.'.'.$extension;
    }

    public function extractOriginalNameFromFilePath($path)
    {
        // Taken from Livewire
        return base64_decode(head(explode('-', last(explode('-meta', str($path)->replace('_', '/'))))));
    }

    public function get_metadata($exif)
    {
        $location = $this->get_image_location($exif);
        $datetime = $this->get_image_datetime($exif);

        $metadata = $this->metadata;

        if($location)
        {
            $metadata['latitude'] = $location['lat'];
            $metadata['longitude'] = $location['lng'];
        } else {
            unset($metadata['latitude']);
            unset($metadata['longitude']);
        }
        if($datetime)
        {
            $metadata['datetime'] = $datetime;
        } else {
            unset($metadata['datetime']);
        }
        if(isset($exif['IFD0']['Orientation'])){
            $metadata['orientation'] = $exif['IFD0']['Orientation'];
        } else {
            unset($metadata['orientation']);
        }
        $this->metadata = $metadata;
    }
     /*
    public static function put_cloud ($file_id, $filename, $tags, $data, $fileuse, $fileuse_id, $filetype=self::MEDIATYPE_IMAGE) {
         list($width, $height, $imagetype) = getimagesizefromstring($data);
         $ext = image_type_to_extension($imagetype, true);
         
         $path = Hash::make(config('company.id') . User::current_user_id() . time()) . $ext;
         $url = Storage::disk('s3')->url($path);
         $size = strlen($data);
         if ($file_id > 0) {
             $oldpath = Media::where('id', $file_id)->pluck('path')->first();
             Storage::disk('s3')->delete($oldpath);
         }
         $file = self::add_file($file_id, $filename, $path, $size, $url, $fileuse, $fileuse_id, $filetype);
         $tags['file_id'] = $file->id;
         $tags['type'] = self::$filetype_strings[$filetype];
         $tags['use'] = self::$fileuse_strings[$fileuse];
         Storage::disk('s3')->put($path, $data, ['Tagging'=>http_build_query($tags)]);
         return $file;
     }*/

    public static function guessExtension($filename)
    {
        $pos = strrpos($filename,'.');

        if($pos===false){
            return '';
        } else {
            return substr($filename,$pos+1);
        }
    }

    public function put_edited_s3 (&$tempFile)
    {
        $ext = $tempFile->guessExtension();

        $name = substr($this->name,-(strlen($ext)+1));

        // format for edit is adding '.edit##' at the end
        $pos = strrpos($name,'.edit');
        if($pos===false){
            $editname = $name.'.edit1';
        } else {
            $num = substr($name,$pos+1);
            $name = substr($name,$pos);
            $editname = $name.'.edit'.strval(intval($num)+1);
        }

        if(method_exists($tempFile,'readStream')){
            $data = $tempFile->readStream();
            $this->size = $tempFile->getSize();
        } elseif(method_exists($tempFile,'get')){
            $data = $tempFile->get();
            $this->size = $tempFile->getSize();
        } elseif(method_exists($tempFile,'toStr')){
            $data = $tempFile->toStr();
            $this->size = strlen($data);
        } else {
            $data = $tempFile;
            $this->size = strlen($data);
        }

        $path = $this->generateHashNameWithOriginalNameEmbedded($this->editname, $ext);

        //$this->url = Storage::disk('s3')->url($path);
    }

    public function put_s3 (&$tempFile, $mediause, $mediause_id, $mediatype=self::MEDIATYPE_IMAGE){
        switch($mediatype){
            case self::MEDIATYPE_IMAGE:
                $ext = $tempFile->guessExtension();
                break;

            case self::MEDIATYPE_PDF:
                $ext = 'pdf';
                break;

            case Self::MEDIATYPE_VIDEO:
                $ext = 'video';
                break;

            case self::MEDIATYPE_XLS:
                $ext = 'xlsx';
                break;

            case self::MEDIATYPE_DOC:
                $ext = 'docx';
                break;

            default:
                $ext = 'unknown';
        }

        if(method_exists($tempFile,'readStream')){
            $data = $tempFile->readStream();
            $this->name = $tempFile->getClientOriginalName();
            $this->size = $tempFile->getSize();
        } elseif(method_exists($tempFile,'get')){
            $data = $tempFile->get();
            $this->name = $tempFile->getClientOriginalName();
            $this->size = $tempFile->getSize();
        } elseif(method_exists($tempFile,'toStr')){
            $this->name = $tempFile->getFilename();
            $data = $tempFile->toStr();
            $this->size = strlen($data);
        } else {
            $data = $tempFile;
            $this->name = 'unknown.unknown';
            $this->size = strlen($data);
        }

        //$path = Hash::make(config('company.id') . Auth::id() . time()) .'.'. $ext;
        $path = $this->generateHashNameWithOriginalNameEmbedded($this->name, $ext);
        
        if ($this->id > 0) {
            // Delete the old file
            // TODO: what about scaled images?
            $this->delete_s3();
            //Storage::disk('s3')->delete($this->path);
        } else {
            if(!$this->company_id){
                $this->company_id = config('company.id');
            }
            $this->user_id = Auth::id()?Auth::id():0;
            $this->media_type = $mediatype;
            $this->media_use = $mediause;
            $this->media_use_id = $mediause_id;
            $this->markup = [];
//            $this->metadata = [];
            $this->resetScaled();
        }

        $this->path = $path;
        $this->url = Storage::disk('s3')->url($path);
        switch($this->media_type){
            case self::MEDIATYPE_IMAGE:
                try {
                    $tempFilename = $tempFile->getPath().'/'.$tempFile->getFilename();
                    $exif = exif_read_data($tempFilename, 'ANYTAG', true);
                    $this->get_metadata($exif);
                } catch (Exception $e){
                    Log::info('Company:'.config('company.id').' Media::put_s3 - Read Exif Data Failed for id='.$this->id.', tempFilename='.$tempFilename.', Exception='.$e->getMessage());
                    $this->metadata = [];
                    $this->markup = [];
                }
                break;

            default:
                $this->metadata = [];
                $this->markup = [];
                break;
        }

        foreach(Media::$filescale_data as $scale=>$scaledata)
        {
            if ($scale != Media::MEDIASCALE_ORIGINAL)
            {
                if ($scaledata['scale']){
                    $this->{$scaledata['field']} = false;
                }
            }
        }

        $this->save();

        $result = Storage::disk('s3')->put($path, $data);
        if(!$result){
            Log::alert('Company:'.config('company.id').' Media::put_s3 - Failed to save Media to S3 for id='.$this->id);
        }
    }

    public function from_s3 ($filename, $path, $mediause, $mediause_id, $mediatype=self::MEDIATYPE_IMAGE)
    {

        if (Storage::disk('s3')->exists($path))
        {
            $data = Storage::disk('s3')->get($path);
        } else {
            Log::error('Media::from_s3 - Could not get media id='.$this->id);
            return false;
        }
        
        try {
            $image = imagecreatefromstring($data);

        } catch (Exception $e){
            Log::error('Media::from_s3 - Could not imagecreatefromstring() media id='.$this->id);
            return false;
        }
        
        if ($this->id > 0) {
            // Delete the old file (skip when completing a pending stub - client is uploading to this path)
            if ($this->s3status !== self::S3STATUS_PENDING) {
               // $this->delete_s3();
            }
        } else {
            if(!$this->company_id){
                $this->company_id = config('company.id');
            }
            $this->user_id = Auth::id()?Auth::id():0;
            $this->media_type = $mediatype;
            $this->media_use = $mediause;
            $this->media_use_id = $mediause_id;
            $this->markup = [];
//            $this->metadata = [];
            $this->resetScaled();
        }

        $this->name = $filename; //self::extractOriginalNameFromFilePath($path);
        $this->size = strlen($data);//$image->getimagesize();

        $this->path = $path;
        $this->url = Storage::disk('s3')->url($path);
        switch($this->media_type){
            case self::MEDIATYPE_IMAGE:
                try {
                    $this->metadata = [];
                    $this->markup = [];
    
                    $exif = exif_read_data($this->url, 'ANYTAG', true);
                    $this->get_metadata($exif);
                } catch (Exception $e){
                    Log::info('Company:'.config('company.id').' Media::from_s3 - Read Exif Data Failed for id='.$this->id.', filename='.$filename.', Exception='.$e->getMessage());
                    $this->metadata = [];
                    $this->markup = [];
                }
                break;

            default:
                $this->metadata = [];
                $this->markup = [];
                break;
        }

        foreach(Media::$filescale_data as $scale=>$scaledata)
        {
            if ($scale != Media::MEDIASCALE_ORIGINAL)
            {
                if ($scaledata['scale']){
                    $this->{$scaledata['field']} = false;
                }
            }
        }

        $this->s3status = self::S3STATUS_AVAILABLE;
        return $this->save();
    }
     
    public function move_s3 (&$tempFile, $mediause, $mediause_id, $mediatype=self::MEDIATYPE_IMAGE)
    {

        if (Storage::disk('s3')->exists($tempFile->temporaryUrl())){
            switch($mediatype){
                case self::MEDIATYPE_IMAGE:
                    $ext = $tempFile->guessExtension();
                    break;
    
                case self::MEDIATYPE_PDF:
                    $ext = 'pdf';
                    break;
    
                case Self::MEDIATYPE_VIDEO:
                    $ext = 'video';
                    break;
    
                case self::MEDIATYPE_XLS:
                    $ext = 'xlsx';
                    break;
    
                case self::MEDIATYPE_DOC:
                    $ext = 'docx';
                    break;
    
                default:
                    $ext = 'unknown';
            }
            $this->name = $filename = $tempFile->getClientOriginalName();
            //$path = Hash::make(config('company.id') . Auth::id() . time()) .'.'. $ext;
            $path = $this->generateHashNameWithOriginalNameEmbedded($filename, $ext);
            Storage::disk('s3')->move($tempFile->temporaryUrl(), $path);
            $data = Storage::disk('s3')->get($path);
        } else {
            Log::error('Media::from_s3 - Could not get media id='.$this->id);
            return false;
        }
        
        try {
            $image = imagecreatefromstring($data);

        } catch (Exception $e){
            Log::error('Media::from_s3 - Could not imagecreatefromstring() media id='.$this->id);
            return false;
        }
        
        if ($this->id > 0) {
            // Delete the old file
            // TODO: what about scaled images?
            $this->delete_s3();
            //Storage::disk('s3')->delete($this->path);
        } else {
            if(!$this->company_id){
                $this->company_id = config('company.id');
            }
            $this->user_id = Auth::id()?Auth::id():0;
            $this->media_type = $mediatype;
            $this->media_use = $mediause;
            $this->media_use_id = $mediause_id;
            $this->markup = [];
//            $this->metadata = [];
            $this->resetScaled();
        }

        $this->name = $filename; //self::extractOriginalNameFromFilePath($path);
        $this->size = strlen($data);//$image->getimagesize();

        $this->path = $path;
        $this->url = Storage::disk('s3')->url($path);
        switch($this->media_type){
            case self::MEDIATYPE_IMAGE:
                try {
                    $this->metadata = [];
                    $this->markup = [];
    
                    //$exif = exif_read_data($image, 'ANYTAG', true);
                    //$this->get_metadata($exif);
                } catch (Exception $e){
                    Log::info('Company:'.config('company.id').' Media::from_s3 - Read Exif Data Failed for id='.$this->id.', filename='.$filename.', Exception='.$e->getMessage());
                    $this->metadata = [];
                    $this->markup = [];
                }
                break;

            default:
                $this->metadata = [];
                $this->markup = [];
                break;
        }

        foreach(Media::$filescale_data as $scale=>$scaledata)
        {
            if ($scale != Media::MEDIASCALE_ORIGINAL)
            {
                if ($scaledata['scale']){
                    $this->{$scaledata['field']} = false;
                }
            }
        }

        $this->save();
    }

    public function put_scaled_to_s3($image, $filescale, $format='webp')
    {
        ob_start();
        switch ($format) {
            case 'png':
                imagepng($image);
                break;
            case 'webp':
                imagewebp($image);
                break;
            case 'jpeg':
                imagejpeg($image);
                break;
            default:
                Log::alert('Company:'.config('company.id').' Media::put_scaled_to_s3 - Unknown format='.$format);
        }
        $data = ob_get_clean();
        
        return $this->put_image_to_s3($data, $filescale, $format);
    }

    public function setAnswer($answer_id){
        $this->answer_id = $answer_id;
        $this->save();
    }
     
     public function put_image_to_s3($data, $filescale, $format)
     {
         $scaledata = self::$filescale_data[$filescale];
         $path = Media::changeExt($this->path, $format, $scaledata['ext']);
         return Storage::disk('s3')->put($path, $data);
     }

     public function put_avatar ($user_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_AVATAR, $user_id, self::MEDIATYPE_IMAGE);
     }

     public function put_field_image ($field_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_FIELD, $field_id, self::MEDIATYPE_IMAGE);
     }

     public function put_field_image_from_s3($field_id, $filename, $path){
        return $this->from_s3($filename, $path, self::MEDIAUSE_FIELD, $field_id, self::MEDIATYPE_IMAGE);
     }

     public function put_answer_image ($field_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_ANSWER, $field_id, self::MEDIATYPE_IMAGE);
     }

     public function put_answer_image_from_s3($field_id, $filename, $path){
        return $this->from_s3($filename, $path, self::MEDIAUSE_ANSWER, $field_id, self::MEDIATYPE_IMAGE);
     }

     public function put_company_logo ($company_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_COMPANYLOGO, $company_id, self::MEDIATYPE_IMAGE);
     }

     public function put_letterhead ($company_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_LETTERHEAD, $company_id, self::MEDIATYPE_IMAGE);
     }

     public function put_customer_logo ($customer_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_CUSTOMERLOGO, $customer_id, self::MEDIATYPE_IMAGE);
     }

     public function put_layout_image ($layout_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_LAYOUT, $layout_id, self::MEDIATYPE_IMAGE);
     }

     public function put_report_pdf($report_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_REPORT, $report_id, self::MEDIATYPE_PDF);
     }

     public function put_quote_pdf($quote_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_QUOTE, $quote_id, self::MEDIATYPE_PDF);
     }

     public function put_invoice_pdf($invoice_id, $tempFile){
        return $this->put_s3($tempFile, self::MEDIAUSE_INVOICE, $invoice_id, self::MEDIATYPE_PDF);
     }


     /*
     public function get_item_files ($item_id) {
         return Media::where('fileuse', self::MEDIAUSE_ITEM)->where('fileuse_id', $item_id)->get();
     }
     
     public function get_page_files ($page_id) {
         return Media::where('fileuse', self::MEDIAUSE_PAGE)->where('fileuse_id', $page_id)->get();
     }*/
     
     public static function get_url($file_id) {
         $application_id = config('company.id');
         
         if (empty($file_id)) {
             return false;
         }
         
         $file = Media::find(intval($file_id));
         if (!empty($file)) {
             if ($file->application_id == $application_id){
                 return $file->url;
             }
         }
         
         return false;
     }
     
     public static function get_urls($files, $scale=Media::MEDIASCALE_800) {
         $application_id = config('company.id');
         
         if (empty($files)) {
             return [];
         }
         
         $file_ids = explode(',', $files);
         
         $urls = [];
         foreach ($file_ids as $file_id) {
             $file = Media::find(intval($file_id));
             if (!empty($file)) {
                 if ($file->application_id == $application_id){
                     $urls[] = $file->image($scale);
                 }
             }
         }
             
         return $urls;
     }
     
    public function sizeFinished($field) {
        $this->$field = 1;
        return $this->save();
    }

    public function deleteImage ()
    {
        $this->image = null;
    }
    
    function get_from_s3()
    {
        $log = 'retrieved '. $this->path;
        $disk = Storage::disk('s3');

        // 1) Try the current path as-is
        if (! $disk->exists($this->path)) {

            // 2) If missing, and filename ends with "_markup.<ext>", try without "_markup"
            $oldPath = $this->path;

            // Split into dir + filename
            $dir = trim((string) pathinfo($oldPath, PATHINFO_DIRNAME));
            $dir = ($dir === '.' ? '' : $dir);

            $filename = (string) pathinfo($oldPath, PATHINFO_BASENAME);

            // Remove trailing _markup before extension
            $fixedFilename = preg_replace('/_markup(\.[^.]+)$/i', '$1', $filename);

            // Only attempt fallback if we actually changed something
            if ($fixedFilename !== $filename) {
                $newPath = ($dir !== '' ? $dir.'/' : '').$fixedFilename;

                if ($disk->exists($newPath)) {
                    // Update model path + save, then use the new file
                    $this->path = $newPath;
                    $this->save();

                } else {
                    Log::error('Media::get_from_s3 - Could not get media id='.$this->id.', tried fallback path='.$newPath);
                    $this->delete();
                    return false;
                }
            } else {
                Log::error('Media::get_from_s3 - Could not get media id='.$this->id.', path='.$oldPath);
                $this->delete();
                return false;
            }
        }

        // Exists on first try
        $data = $disk->get($this->path);
        
        try {
//            Log::debug('Media::get_from_s3 - ImageCreateFromString');
            
            // Check if HEIC/HEIF and convert using ImageMagick
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($data);
            
            if (in_array($mimeType, ['image/heic', 'image/heif']) || 
                preg_match('/\.heic$/i', $this->path) || 
                preg_match('/\.heif$/i', $this->path)) {
                
                if (extension_loaded('imagick')) {
                    $imagick = new \Imagick();
                    $imagick->readImageBlob($data);
                    $imagick->setImageFormat('jpeg');
                    $data = $imagick->getImageBlob();
                    $imagick->clear();
                    $imagick->destroy();
                    $log .= ', converted from HEIC';
                } else {
                    Log::warning('Media::get_from_s3 - HEIC image but ImageMagick not available, skipping id='.$this->id);
                    $this->delete();
                    return false;
                }
            }
            
            $this->image = imagecreatefromstring($data);
            if (!$this->image) {
                Log::error('Media::get_from_s3 - imagecreatefromstring failed for id='.$this->id);
                $this->delete();
                return false;
            }
            $log .= ', created';

//            Log::debug('Media::get_from_s3 - getRotation');
            $rotation = $this->getRotation();
            if($rotation){
//                Log::debug('Media::get_from_s3 - Rotating');
                // TODO:: This is failing silently (no exception) on some media ex:5446,5447 - don't know why
                $this->image = imagerotate($this->image, $rotation, 0);
//                Log::debug('Media::get_from_s3 - Rotated');
                $log .= ', rotated='.$rotation;
            }
            if($this->getFlipped()){
//                Log::debug('Media::get_from_s3 - Flipping');
                $this->image = imageflip($this->image, IMG_FLIP_HORIZONTAL);
//                Log::debug('Media::get_from_s3 - Flipped');
                $log .= ', flipped';
            }
            Log::debug('Media::get_from_s3 - done: '.$log);
            return true;
        } catch (Exception $e) {
            Log::error('Cron: Media::get_from_s3 - Failed create image for file_id='. $this->id.', results='.$log.', error='.$e->getMessage());
            $this->delete();
            return false;
        }
    }

    function is_gd_image() {
        if ( is_resource( $this->image ) && 'gd' === get_resource_type( $this->image )
            || is_object( $this->image ) && $this->image instanceof GdImage
        ) {
            return true;
        }
     
        return false;
    }

    public static function getExifRotation($orientation)
    {
        switch($orientation) {
            case 1:
            case 2:
                return 0;
            case 3:
            case 4:
                return 180;
            case 5:
            case 6:
                return -90;
            case 7:
            case 8:
                return 90;

            default:
                return 0;
        }
    }

    public function getRotation()
    {
        /*
        * 
        * 1 = 0 degrees: the correct orientation, no adjustment is required.
        * 2 = 0 degrees, mirrored: image has been flipped back-to-front.
        * 3 = 180 degrees: image is upside down.
        * 4 = 180 degrees, mirrored: image has been flipped back-to-front and is upside down.
        * 5 = 90 degrees: image has been flipped back-to-front and is on its side.
        * 6 = 90 degrees, mirrored: image is on its side.
        * 7 = 270 degrees: image has been flipped back-to-front and is on its far side.
        * 8 = 270 degrees, mirrored: image is on its far side.
        */

        if ($this->metadata && isset($this->metadata['orientation']))
        {
            return self::getExifRotation($this->metadata['orientation']);
        } else {
            return 0;
        }
    }

    public function getFlipped()
    {
        return ($this->metadata && isset($this->metadata['orientation']) && in_array($this->metadata['orientation'], [2,4,5,7]));
    }
          
    function getWidth()
    {    
        return imagesx($this->image);
    }
    
    function getHeight()
    {
        return imagesy($this->image);
    }

    function resizeFill($width, $height)
    {
        if($width/$height < $this->getWidth()/$this->getHeight()){
            return $this->resizeToWidth($width);
        } else {
            return $this->resizeToHeight($height);
        }
    }
     
    function resizeMax($size)
    {
        if($this->getHeight() > $this->getWidth())
        {
            return $this->resizeToHeight($size); 
        } else {
            return $this->resizeToWidth($size);
        }
    }
    
    function resizeToHeight($height)
    {
        $this->scaled = $height / $this->getHeight();
        $width = $this->getWidth() * $this->scaled;
        return $this->resize($width,$height);
    }
    
    function resizeToWidth($width)
    {
        $this->scaled = $width / $this->getWidth();
        $height = $this->getheight() * $this->scaled;
        return $this->resize($width,$height);
    }
     
     function scale($scale)
     {
         $width = $this->getWidth() * $scale/100;
         $height = $this->getheight() * $scale/100;
         return $this->resize($width,$height);
     }
     
     function resize($width,$height)
     {
        $new_image = imagecreatetruecolor($width, $height);
//        imagecolortransparent($new_image, imagecolorallocatealpha($new_image, 0, 0, 0, 127));
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        return $new_image;
     }

    public function addMetadata(&$new_image, $font, $metadata)
    {
        if(isset($metadata['watermark']) && !$metadata['watermark']){
            return;
        }
        
    //    $backgroundColor = imagecolorallocatealpha($new_image, 0, 0, 0, 64);
        $textColor = imagecolorallocate($new_image, 255, 255, 255);

        $height = imagesy($new_image);
        $width = imagesx($new_image);
        $fontheight = imagefontheight($font);
        $fontwidth = imagefontwidth($font);

        $lines = [];

        if(isset($metadata['name'])){
            $lines[] = $metadata['name'];
        }
        $comment = Answer::getComments($this->id, $this->media_use_id);
        if($comment && (strlen($comment)>0)){
            $lines[] = $comment;
        }
        if(isset($metadata['datetime'])){
            $lines[] = $metadata['datetime'];
        }
        if(isset($metadata['latitude'])&&isset($metadata['longitude'])){
            $lines[] = number_format($metadata['latitude'],4).','.number_format($metadata['longitude'],4);
        }

        $y = intval($height-($fontheight*count($lines))-count($lines)-1);
    //    imagefilledrectangle($new_image, 0, $y, $width, $height, $backgroundColor);

        foreach($lines as $line){
            $x = max(0,($width-(strlen($line)*$fontwidth)-1));
            imagestring($new_image, $font, $x, $y+1, $line, $textColor);
            $y += (1+$fontheight);
        }
    }

    public function addMarkup(&$new_image, $stroke)
    {
        $arrowColor = imagecolorallocate($new_image, 255, 0,  0);
        imagesetthickness($new_image, $stroke);

        if(isset($this->markup['arrows'])){
            foreach($this->markup['arrows'] as $arrow){
                $x = $arrow['x'] * $this->scaled;
                $y = $arrow['y'] * $this->scaled;
                imageline($new_image, $x, $y, $x, $y-(3*$stroke), $arrowColor);
                imageline($new_image, $x, $y, $x+(3*$stroke), $y, $arrowColor);
                imageline($new_image, $x, $y, $x+(6*$stroke), $y-(6*$stroke), $arrowColor);
            }
        }

        if(isset($this->markup['paths'])){
            foreach($this->markup['paths'] as $path){
                $first = true;
                $prevX=0;
                $prevY=0;
                foreach($path as $segment){
                    if($first){
                        $prevX = $segment['x'] * $this->scaled;
                        $prevY = $segment['y'] * $this->scaled;
                        $first = false;
                    } else {
                        $currX = $segment['x'] * $this->scaled;
                        $currY = $segment['y'] * $this->scaled;
                        imageline($new_image, $prevX, $prevY, $currX, $currY, $arrowColor);
                        $prevX = $currX;
                        $prevY = $currY;
                    }
                }    
            }
        }

        if(isset($this->markup['rectangles'])){
            foreach($this->markup['rectangles'] as $rectangle){
                $x1 = $rectangle['x1'] * $this->scaled;
                $y1 = $rectangle['y1'] * $this->scaled;
                $x2 = $rectangle['x2'] * $this->scaled;
                $y2 = $rectangle['y2'] * $this->scaled;
                imagerectangle($new_image, $x1, $y1, $x2, $y2, $arrowColor);
            }
        }

        if(isset($this->markup['circles'])){
            foreach($this->markup['circles'] as $circle){
                $centerX = $circle['centerX'] * $this->scaled;
                $centerY = $circle['centerY'] * $this->scaled;
                $radiusX = $circle['radiusX'] * $this->scaled;
                $radiusY = $circle['radiusY'] * $this->scaled;
                imageellipse($new_image, $centerX, $centerY, $radiusX*2, $radiusY*2, $arrowColor);
            }
        }
    }

    public function getImage()
    {
        return $this->image;
    }

    public function resetScaled(){
        foreach (Media::$filescale_data as $scale_data){
            if($scale_data['scale']){
                $this->{$scale_data['field']} = false;
            }
        }
    }

    public static function saveMediaMarkup($media_id, $arrows, $paths)
    {
        $media = Media::findOrFail($media_id);
        $media->saveMarkup($arrows, $paths);
    }

    public function saveMarkup($arrows, $paths)
    {
        $this->markup = ['arrows'=>$arrows, 'paths'=>$paths];
        $this->resetScaled();
        $this->save();
    }

    public function addMeta($key,$value)
    {
        $metadata = $this->metadata;
        $metadata[$key] = $value;
        $this->metadata = $metadata;
    }

    public function setName($name,$watermark=true,$orientation=null)
    {
        $this->addMeta('name',$name);
        $this->addMeta('watermark',$watermark);
        if($orientation){
            $this->addMeta('orientation',$orientation);
        }
        $this->resetScaled();
        $this->save();
    }

    public function resizing()
    {
        return ($this->resize == self::RESIZE_INPROGRESS);
    }

    public function resizeStart()
    {
        $this->resize = self::RESIZE_INPROGRESS;
        $this->save();
    }

    public function resizeFinish()
    {
        $this->resize = self::RESIZE_NONE;
        $this->save();
    }

    public function resizeError()
    {
        $this->resize = self::RESIZE_ERROR;
        $this->save();
        Log::error('Media::resizeError - media_id='.$this->id);
    }

    /**
     * Dispatch MediaScale job if this image needs scaling (single place for all scale dispatch).
     */
    public function dispatchScaleIfNeeded(): void
    {
        if ($this->media_type !== self::MEDIATYPE_IMAGE) {
            return;
        }
        if (empty($this->path)) {
            return;
        }
        foreach (self::$filescale_data as $scaledata) {
            if (! empty($scaledata['scale'])) {
                $field = $scaledata['field'];
                if (empty($this->$field)) {
                    \App\Jobs\MediaScale::dispatch($this->fresh() ?? $this);
                    return;
                }
            }
        }
    }

/*
    public function urlScaled($scale){
        if(!isset(Media::$filescale_data[$scale])){
            $scale = Media::MEDIASCALE_800;
        }
        $url = substr($this->url,0,strrpos($this->url,'.')) .'-'. Media::$filescale_data[$scale]['ext'].'.jpeg';
    }*/

    function company()
    {
        return $this->belongsTo(Company::class);
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }
}