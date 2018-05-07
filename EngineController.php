<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\AdModel;

class EngineController extends Controller
{
    public function actionIndex()
    {
        $now = new \DateTime();
        $date_now = date_format($now, 'Y-m-d H:i:s');
        $date_del =$now->modify('-90 day');
        $date_del = date_format($date_del,'Y-m-d H:i:s');

        $now = new \DateTime();
        $date_3day =$now->modify('+3 day');
        $date_3day = date_format($date_3day,'Y-m-d H:i:s');

        $now = new \DateTime();
        $date_2day =$now->modify('+2 day');
        $date_2day = date_format($date_2day,'Y-m-d H:i:s');

        $now = new \DateTime();
        $date_10day =$now->modify('-10 day');
        $date_10day = date_format($date_10day,'Y-m-d H:i:s');

        $now = new \DateTime();
        $date_11day =$now->modify('-11 day');
        $date_11day = date_format($date_11day,'Y-m-d H:i:s');

        // vacancy
        $q = 'DELETE v FROM vacancy v  WHERE date_end< "'.$date_del.'"';
        $res = Yii::$app->db->createCommand($q)->execute();
        echo ' vacancy delete '.$res. "\n";

        $is_vacancy = 1;
        //почта
        $from_name = Yii::$app->params['supportName'];
        $from_email = Yii::$app->params['supportEmail'];

//        $q = 'select v.id,v.slug,v.title,v.price_start,v.price_end,u.photo,u.email,u.username from  vacancy v  ';
        $q = 'select v.id,v.slug,v.title,v.location ,u.photo,u.email,u.username from  vacancy v  ';
        $q .= ' join user u on v.user_id=u.id where v.status ='.AdModel::ACTIVE.' and  v.date_end< "'.$date_now.'"';
        $res_mail = Yii::$app->db->createCommand($q)->queryAll();
        if (is_array($res_mail) && count($res_mail)>0){
            self::send_ads($is_vacancy,$res_mail,'ads_arxive','Срок размещения Вашего объявления истекает',$from_email,$from_name);

            // меняем статус
            $q = 'update vacancy set status ='.AdModel::ARCHIVE.' where  date_end< "'.$date_now.'"';
            $res_up = Yii::$app->db->createCommand($q)->execute();

            echo 'vacancy update status= '.$res_up. "\n";

        }
		else  {
			echo 'vacancy update status= 0'."\n";
		}	
///////////////////////////////////////////////////  3 day
        $q = 'select v.id,v.slug,v.title,v.location ,u.photo,u.email,u.username from  vacancy v  ';
        $q .= ' join user u on v.user_id=u.id where v.status ='.AdModel::ACTIVE.' and  v.date_end > "'.$date_2day.'"';
        $q .= ' and v.date_end <= "'.$date_3day.'"';
        $res_mail = Yii::$app->db->createCommand($q)->queryAll();
        self::send_ads($is_vacancy,$res_mail,'ads_3day','Срок размещения Вашего объявления истекает через 3 дня',$from_email,$from_name);

///////////////////////////////////////////////////  10 day
        $q = 'select v.id,v.slug,v.title,v.location ,u.photo,u.email,u.username from  vacancy v  ';
        $q .= ' join user u on v.user_id=u.id where v.status ='.AdModel::ARCHIVE.' and  v.date_end > "'.$date_11day.'"';
        $q .= ' and v.date_end < "'.$date_10day.'" group by v.user_id';
        $res_mail = Yii::$app->db->createCommand($q)->queryAll();
        self::send_ads($is_vacancy,$res_mail,'ads_10day','Вакансия в архиве',$from_email,$from_name);


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // resume
         $q = 'DELETE v FROM resume v  WHERE date_end< "'.$date_del.'"';
         $res = Yii::$app->db->createCommand($q)->execute();
         echo ' resume delete '.$res."\n";

        $is_vacancy = 0;
        //почта
        $q = 'select v.id,v.slug,v.title,v.location ,u.photo,u.email,u.username from  resume v ';
        $q .= ' join user u on v.user_id=u.id where v.status ='.AdModel::ACTIVE.' and  v.date_end< "'.$date_now.'"';
        $res_mail = Yii::$app->db->createCommand($q)->queryAll();
        if (is_array($res_mail) && count($res_mail)>0) {
            self::send_ads($is_vacancy,$res_mail,'ads_arxive','Срок размещения Вашего резюме истекает',$from_email,$from_name);
            // меняем статус
            $q = 'update resume set status =' . AdModel::ARCHIVE . ' where  date_end< "' . $date_now . '"';
            $res_up = Yii::$app->db->createCommand($q)->execute();

            echo 'resume update status= ' . $res_up . "\n";
        } else  echo 'resume update status= 0 \n';

        // предупреждение за три дня
        $q = 'select v.id,v.slug,v.title,v.location ,u.photo,u.email,u.username from  resume v ';
        $q .= ' join user u on v.user_id=u.id where v.status ='.AdModel::ACTIVE.' and  v.date_end> "'.$date_2day.'"';
        $q .= ' and v.date_end <= "'.$date_3day.'"';
        $res_mail = Yii::$app->db->createCommand($q)->queryAll();
        self::send_ads($is_vacancy,$res_mail,'ads_3day','Срок размещения Вашего резюме истекает через 3 дня',$from_email,$from_name);

///////////////////////////////////////////////////  10 day
        $q = 'select v.id,v.slug,v.title,v.location ,u.photo,u.email,u.username from  resume v  ';
        $q .= ' join user u on v.user_id=u.id where v.status ='.AdModel::ARCHIVE.' and  v.date_end > "'.$date_11day.'"';
        $q .= ' and v.date_end < "'.$date_10day.'" group by v.user_id';
        $res_mail = Yii::$app->db->createCommand($q)->queryAll();
        self::send_ads($is_vacancy,$res_mail,'ads_10day','Резюме в архиве',$from_email,$from_name);

        exit;
    }

    public static function send_ads($is_vacancy,$res_mail,$text,$subject,$from_email,$from_name)
    {
        if (is_array($res_mail) && count($res_mail) > 0) {
            foreach ($res_mail as $row) {
                Yii::$app->mailer->compose($text, [
                    'is_vacancy' => $is_vacancy,
                    'id' => $row['id'],
                    'slug' => $row['slug'],
                    'title' => $row['title'],
                    'location' => $row['location'],
                    'photo' => $row['photo'],
                    'username' => $row['username'],
                ])
                    ->setTo($row['email'])
                    ->setFrom([$from_email => $from_name])
                    ->setSubject($subject)
                    ->send();
            }
        }
    }
}
?>