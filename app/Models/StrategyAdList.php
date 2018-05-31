<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/29
 * Time: 12:16
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StrategyAdList extends Model
{
    protected $table = "strategy_ad_list";
    public $timestamps = false;

    public function getList($map)
    {
        return DB::table($this->table)->where($map)->orderBy("weight", "desc")->get();
    }
}