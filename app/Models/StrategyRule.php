<?php
/**
 * Created by PhpStorm.
 * User: chenrenli
 * Date: 2018/5/29
 * Time: 09:54
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StrategyRule extends Model
{
    protected $table = "strategy_rule";
    public $timestamps = false;


    public function getList($strategy_ids = [])
    {
        $filestr = implode(",", $strategy_ids);
        return DB::table($this->table)->whereIn("strategy_id", $strategy_ids)->orderByRaw(DB::raw("FIELD(strategy_id,$filestr)"))->get();
    }

}