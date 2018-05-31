<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Strategy extends Model
{
    //
    protected $table = "strategy";
    public $timestamps = false;

    /**
     * 获取数据列表
     * @param $map
     */
    public function getList($map, $limit = 200)
    {
        return DB::table($this->table)->where($map)->orderBy("weight", "desc")->take($limit)->get();
    }
}
