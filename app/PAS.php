<?php


namespace App;

use Illuminate\Support\Facades\DB;

class PAS extends BaseModel
{

    protected $table = 'pas';

    public function scopeWw($query, $ww, $year)
    {
        $query->select('*')
            // - actual_work
            ->addSelect(DB::raw("round(planned_work * planned_capacity, 1) planned"))
            ->addSelect(DB::raw("concat(
                case when `order` is null then \"\" else `order` end,
                `op_number`,
                case when `maintenance_plan` = \" \" then \"\" else `maintenance_plan` end,
                case when `item` = \" \" then \"\" else `item` end,
                `call_number`
                ) `key`"))
            ->whereRaw("week(scheduled_for, 6) = " . $ww)
            ->whereRaw("year(scheduled_for) = " . $year)
            ->whereIn('report_id', function($q) use ($ww) {
                $q->addSelect(DB::raw("(" . self::maxId($ww)->toSql() . ")"));
            });
    }

    public function scopeScheduledBetween($query, $from, $to)
    {
        $query->whereBetween("scheduled_for", [$from, $to]);
    }

    public static function maxId($ww)
    {
        return DB::table('pas')->select([DB::raw("max(report_id)")])->whereRaw('week(scheduled_for, 6) = ' . $ww);
    }

    public function scopeOnlyRequiredOperations($query)
    {
        return $query->whereIn('control_key', ['8F01']);
    }
}