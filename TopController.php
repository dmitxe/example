<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use Yii;
use yii\console\Controller;

class TopController extends Controller
{
    public function actionIndex($message = 'hello world')
    {
        $now = new \DateTime();
        $date_now = date_format($now,'Y-m-d H:i:s');
        $tables = ['target_city','target_region','target_country','target_city_resume','target_region_resume','target_country_resume'];
        $update = [];
        foreach ($tables as $table) {
            $q = 'update ' . $table.' SET ';
            $q .= ' date_create1= if (date_end1< "' .$date_now. '" and id_vacancy1 > 0,"0",date_create1),';
            $q .= ' date_create2= if (date_end2< "' .$date_now. '" and id_vacancy2 > 0,"0",date_create2),';
            $q .= ' date_create3= if (date_end3< "' .$date_now. '" and id_vacancy3 > 0,"0",date_create3),';
            $q .= ' date_end1= if (date_end1< "' .$date_now. '" and id_vacancy1 > 0,"0",date_create1),';
            $q .= ' date_end2= if (date_end2< "' .$date_now. '" and id_vacancy2 > 0,"0",date_create2),';
            $q .= ' date_end3= if (date_end3< "' .$date_now. '" and id_vacancy3 > 0,"0",date_create3), ';
            $q .= ' id_vacancy1= if (date_end1< "' .$date_now. '" and id_vacancy1 > 0,0,id_vacancy1),';
            $q .= ' id_vacancy2= if (date_end2< "' .$date_now. '" and id_vacancy2 > 0,0,id_vacancy2),';
            $q .= ' id_vacancy3= if (date_end3< "' .$date_now. '" and id_vacancy3 > 0,0,id_vacancy3)';
            $q .= ' where (date_end1< "' . $date_now . '" and id_vacancy1 > 0 ) or ';
            $q .= ' (date_end2< "' . $date_now . '" and id_vacancy2 > 0 ) or ';
            $q .= ' (date_end3< "' . $date_now . '" and id_vacancy3 > 0 )  ';
            $target = Yii::$app->db->createCommand($q)->execute();
            $update[$table] = $target;
        }
    }

}
