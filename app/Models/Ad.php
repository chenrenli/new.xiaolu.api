<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/29
 * Time: 13:59
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Ad extends Model
{
    protected $table = "ad";
    public $timestamps = false;

    public function getAdList($adIds=[])
    {
        return DB::table($this->table)->leftJoin("ad_union_extra", "ad.id", "=", "ad_union_extra.ad_id")->whereIn("ad.id",$adIds)->get();
    }
}