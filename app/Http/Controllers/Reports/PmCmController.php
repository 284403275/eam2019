<?php


namespace App\Http\Controllers\Reports;


use App\Http\Controllers\Controller;
use App\SAP\Excel\PmCmReport;
use App\SAP\Models\Equipment;
use App\SAP\Models\PmCmRatio;

class PmCmController extends Controller
{
    public function index()
    {
        $systems = [
            '81F-FA-AMEX',
            '81F-FB-AMEX',
            '81F-FC-AMEX',
            '81F-FA-ACEX',
            '81F-FB-ACEX',
            '81F-FC-ACEX',
            '81F-FA-MUA0',
            '81F-FA-MUA1',
            '81F-FA-MUA2',
            '81F-FB-MUA0',
            '81F-FB-MUA1',
            '81F-FB-MUA2',
            '81F-FC-MUA0',
            '81F-FC-MUA2',
            '81F-FC-MUA3',
            '81F-FC-MUA4',
            '81F-FC-MUA5',
            '81F-CA-HSSO',
            '81F-CC-HSSO',
            '81F-CA-CTWS',
            '81F-CC-CTWS',
            '81F-CA-CHWS',
            '81F-CA-CWS0',
            '81F-CA-CWS1',
            '81F-CC-CHWS',
            '81F-CC-CWS0',
            '81F-CC-CWS1',
            '81F-FA-PCW1',
            '81F-FA-PCW2',
            '81F-FA-PCW3',
            '81F-FB-PCW3',
            '81F-FA-PVAC1',
            '81F-FA-PVAC2',
            '81F-FC-PVAC',
            '81F-FA-SOEX',
            '81F-FB-SOEX',
            '81F-FA-RCCU',
            '81F-FB-RCCU',
            '81F-FC-RCCU',
            '81F-FC-PCW',
            '81F-FC-PCW1',

            '81F-FA-UVLP',
            '81F-FB-UVLP',
            '81F-FC-UVLP',

            '81F-FA-UPWC',

            '81F-CB-HFTR',

            '81F-FB-ANIS',
            '81F-FC-ANIS'
        ];

//        dd($this->getPhase('81F-FA-PCW1'), $this->getSystem('81F-FA-PCW1'));

        $equipments = Equipment::whereFlocsIn($systems)->get()->pluck('equipment')->chunk(900);

//        dd($equipments);

        $ratio = $equipments->transform(function($list) {
            return PmCmRatio::equipment($list->toArray())
                ->forYear(2018)
                ->get()
                ->groupBy('floc')->transform(function ($system) {
                    return $system->groupBy('eq_type')
                        ->transform(function ($group) {
                            return [
                                'cm' => $group->sum('cm'),
                                'pm' => $group->sum('pm'),
                                'cm_work' => $group->sum('cm_work'),
                                'pm_work' => $group->sum('pm_work'),
                                'work_ratio' => $this->ratio($group->sum('pm_work'), $group->sum('cm_work')),
                                'floc_description' => $group->pluck('floc_description')->first(),
                                'eq_description' => $group->pluck('eq_description')->first(),
                                'ratio' => $group->sum('pm') / gcd($group->sum('pm'), $group->sum('cm')) . ':' . $group->sum('cm') / gcd($group->sum('pm'), $group->sum('cm')),
                                'system' => $group->pluck('floc')->first(),
                                'eq_type' => $group->pluck('eq_type')->first(),
                                'tag' => $group->pluck('tag_id')->count()
                            ];
                        });
                });
        })->flatten(2);

        return $ratio->groupBy('system')->map(function($system) {
            return [
                'floc' => $system->pluck('system')->first(),
                'phase' => $this->getPhase($system->pluck('system')->first()),
                'system' => $this->getSystem($system->pluck('system')->first()),
                'description' => $system->pluck('floc_description')->first(),
                'cm_work' => $system->sum('cm_work'),
                'pm_work' => $system->sum('pm_work'),
                'cm' => $system->sum('cm'),
                'pm' => $system->sum('pm'),
                'work_ratio' => $this->ratio($system->sum('pm_work'), $system->sum('cm_work')),
                'ratio' => $system->sum('pm') / gcd($system->sum('pm'), $system->sum('cm')) . ':' . $system->sum('cm') / gcd($system->sum('pm'), $system->sum('cm')),
                'simple_work' => $this->simpleRatio($system->sum('pm_work'), $system->sum('cm_work')),
                'simple_order' => $this->simpleRatio($system->sum('pm'), $system->sum('cm'))
            ];
        })->values()->groupBy('phase')->map(function($phases) {
            return $phases->groupBy('system')->map(function($system) {
                return [
                    'hours' => $system->pluck('simple_work')->first(),
                    'm-ratio' => $system->pluck('simple_order')->first()
                ];
            });
        })->sort();

        return new PmCmReport($ratio);

        return call_user_func_array('array_merge', $ratio->toArray());
    }

    public function ratio($a, $b)
    {
        $a = round($a);
        $b = round($b);

        if($a == 0 && $b != 0)
            return '0:' . $b;

        if($b == 0 && $a != 0)
            return $a . ':0';

        if($a == 0 && $b == 0)
            return '0:0';

        return $a / gcd($a, $b) . ':' . $b / gcd($a, $b);
    }

    public function simpleRatio($pm, $cm)
    {
        $pm = round($pm);
        $cm = round($cm);
        $pmr = $pm / gcd($pm, $cm);
        $cmr = $cm / gcd($pm, $cm);
        //return $pmr > $cmr ? round($pmr / $cmr) . ':1' : '1:' . round($cmr / $pmr);

        if($pmr > $cmr) {
            if($cmr != 0)
                return round($pmr / $cmr) . ':1';
            return $pm . ':0';
        }
        if($pmr != 0)
            return '1:' . round($cmr / $pmr);
        return '0:' . $cm;
    }

    public function getPhase($floc)
    {
        preg_match('/-([^-]+)-/', $floc, $phase);

        switch ($phase[1]) {
            case 'FA': return 'Phase 1';
            case 'FB': return 'Phase 2';
            case 'FC': return 'Phase 3';
            case 'CA': return 'Cub A';
            case 'CB': return 'Cub B';
            case 'CC': return 'Cub C';
            case 'CG': return 'Campus General';
            default: return 'Unknown';
        }
    }

    public function getSystem($floc)
    {
        preg_match('/-[^-]+-(.[a-zA-Z]+)/', $floc, $phase);

        return $phase[1];
    }
}