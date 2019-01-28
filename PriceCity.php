<?php

namespace app\models;

use Yii;
use app\models\PriceCountry;

/**
 * This is the model class for table "price_city".
 *
 * @property integer $city_id
 * @property string $category_id
 * @property string $price
 * @property string $price_resume
 */
class PriceCity extends \yii\db\ActiveRecord
{
    public $country_id = 1;
    public $region_id = null;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'price_city';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['city_id', 'price', 'price_resume'], 'required'],
            [['city_id', 'category_id', 'country_id'], 'integer'],
            [['price','price_resume'], 'number'],
        ];
    }

    /**
     * Get country name.
     * @return mixed
     */
    public function getCountryName()
    {
        if (!empty($this->country_id)) {
            if ($this->country_id == 1) {
                return 'Россия';
            }
            $title = 'title_' . Yii::$app->language;
            $model = Country::findOne(['country_id' => $this->country_id]);
            if ($model !== null) {
                return $model->{$title};
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * Get region name.
     * @return mixed
     */
    public function getRegionName()
    {
        if (!empty($this->region_id)) {
            $title = 'title_' . Yii::$app->language;
            $model = Region::findOne(['region_id' => $this->region_id]);
            if ($model !== null) {
                return $model->{$title};
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * Get city name.
     * @return mixed
     */
    public function getCityName()
    {
        if (!empty($this->city_id)) {
            $title = 'title_' . Yii::$app->language;
            $model = City::findOne(['city_id' => $this->city_id]);
            if ($model !== null) {
                return $model->{$title};
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * Обработка трех первых закрепленных вакансий/резюме.
     * @return mixed
     */
    public static function getFirst3($type_table,$id,$category_id, $type_engine='')
    {
        $id = (int) $id;
        if (is_array($category_id)) {
            $main_category = $category_id[0];
            $q  = 'select parent_id from category where id='.$main_category;
            $parent_id = Yii::$app->db->createCommand($q)->queryScalar();
            $_category = $category_id;
            $not_in = implode(',',$category_id);
        } else {
            $main_category = $category_id;
            $q  = 'select parent_id from category where id='.$category_id;
            $parent_id = Yii::$app->db->createCommand($q)->queryScalar();
            $_category[] = $category_id;
            $not_in = $category_id;
        }

        $arr = [];
        if ($parent_id == 0) {
            $q = 'select * from target_'.$type_table.$type_engine.' where '.$type_table.'_id ='.$id.' and category_id = '.$main_category.' ';
            $q .= ' and ( (id_vacancy1 > 0 ) or (id_vacancy2 > 0) or ( id_vacancy3 > 0 ))';
            $res = Yii::$app->db->createCommand($q)->queryOne();
            for ($i = 1; $i <= 3; $i++)
                if ($res['id_vacancy' . $i] > 0) $arr[$res['id_vacancy' . $i]] = $res['id_vacancy' . $i];
            unset($_category[0]);
        }

        $count_top = count($arr);
        if ($count_top == 3) return $arr;

        $count_add = 3-$count_top;
        if (count($_category) > 0) {
            $category = implode(',',$_category);

            $q = 'select * from target_'.$type_table.$type_engine.' where '.$type_table.'_id ='.$id.' and category_id in ( '.$category.' )';
            $q .= ' and ( (id_vacancy1 > 0 ) or (id_vacancy2 > 0)  or ( id_vacancy3 > 0 ))';
            $res = Yii::$app->db->createCommand($q)->queryAll();

            PriceCountry::addTop3($arr,$res,$count_add);
        } elseif ($main_category == 0) {
            $q  = 'select id from category ';
            $res_cat = Yii::$app->db->createCommand($q)->queryAll();
            $s = PriceCity::array_to_str($res_cat);

            $q = 'select * from target_'.$type_table.$type_engine.' where '.$type_table.'_id ='.$id.' and category_id in '.$s.'';
            $q .= ' and ( (id_vacancy1 > 0 ) or (id_vacancy2 > 0)  or ( id_vacancy3 > 0 ))';
            $res = Yii::$app->db->createCommand($q)->queryAll();

            PriceCountry::addTop3($arr,$res,$count_add);
        }

        $count_top = count($arr);
        if ($count_top == 3) return $arr;

        if ($parent_id > 0) {
            $q = 'select parent_id from category  where id ='.$main_category;
            $main_category = Yii::$app->db->createCommand($q)->queryScalar();

            $q = 'select * from target_'.$type_table.$type_engine.' where '.$type_table.'_id = :id and category_id='.$main_category;
            $q .= ' and ( (id_vacancy1 > 0 ) or (id_vacancy2 > 0)  or ( id_vacancy3 > 0 ))';
            $res = Yii::$app->db->createCommand($q)->bindParam(':id',$id)->queryAll();
            PriceCountry::addTop3($arr,$res,$count_add);
        }

        return $arr;
    }

    public static function array_to_str($res)
    {
        $s = '(';
        foreach ($res as $row) $s .= $row['id'].',';
        $s = substr($s,0,-1); $s .= ')';
        return $s;
    }


    /**
     * Проверка на публикацию в топе.
     * @return mixed
     */
    public static function isPublishTop( $vacancy_id, $res)
    {
        if (($res['id_vacancy1'] == $vacancy_id) || ($res['id_vacancy2'] == $vacancy_id) || ($res['id_vacancy3'] == $vacancy_id)) return true;
        else return false;
    }

    /**
     * Обновление топа.
     * @return mixed
     */
    public static function updateFinishTop($res,$type_table)
    {
        $now = new \DateTime();
        $s = '';
        for ($i=1; $i<=3; $i++) {
            if ( ($res['id_vacancy'.$i] > 0) && ( new \DateTime($res['date_end'.$i]) <= $now)) {
                $s .= 'id_vacancy'.$i.'=0, date_create'.$i.' = "0", date_end'.$i.'="0",';
            }
        }
        $answ = $res;
        if ($s){
            $s = substr($s,0,-1);
            $q = 'update target_'.$type_table.' set '.$s.' where '.$type_table.'_id =:id and category_id='.$res['category_id'];
            $result = Yii::$app->db->createCommand($q)->bindParam(':id',$res[$type_table.'_id'])->execute();

            $q = 'select * from target_'.$type_table.'  where '.$type_table.'_id =:id and category_id = '.$res['category_id'];
            $answ = Yii::$app->db->createCommand($q)->bindParam(':id',$res[$type_table.'_id'])->queryOne();
        }
        return  $answ;
    }

    /**
     * Получение цены для категории.
     * @return mixed
     */
    public static function getPriceCategory($category_id, $type_engine = '')
    {
        $cache_id = 'price_category_'.$category_id.$type_engine;
        $data = Yii::$app->cache->get($cache_id);
        if ($data  && Yii::$app->params['is_cache'])
            return $data;

        $field_price = 'price'.$type_engine;
        $q = 'select '.$field_price.' from category  where id = :id ';
        $res = Yii::$app->db->createCommand($q)->bindParam(':id',$category_id)->queryOne();
        if ($res[$field_price]) {
            Yii::$app->cache->set($cache_id,$res[$field_price],6000);
            return $res[$field_price];
        }
    }

    /**
     * Получение для цены всех категорий.
     * @return mixed
     */
    public static function getPriceAllCategory($type_price)
    {
        $cache_id = 'price_allcategory_'.$type_price;
        $data = Yii::$app->cache->get($cache_id);
        if ($data  && Yii::$app->params['is_cache'])
            return $data;

        $q = 'select '.$type_price.' from price_country  where country_id = 0 ';
        $target = Yii::$app->db->createCommand($q)->queryOne();
        Yii::$app->cache->set($cache_id,$target[$type_price],6000);
        return $target[$type_price];
    }

    /**
     * GПолучение цены для региона.
     * @return mixed
     */
    public static function getPriceRegion($region_id, $type_price, $category_id = null, $type_engine='')
    {
        if ($type_price == 'price_city') {
            $q = 'select if( (select count(*) from price_region  where region_id =:id)>0 ,';
            $q .= ' (select price'.$type_engine.' from  price_region  where region_id =:id), ';
            $q .= ' (select '.$type_price.$type_engine.' from price_country  where country_id = 1) ) as '.$type_price;
            $target = Yii::$app->db->createCommand($q)->bindParam(':id', $region_id)->queryOne();
            $price_country = $target[$type_price];
            return $price_country;
        } else {
            $q = 'select price'.$type_engine.', price_parent'.$type_engine.', price_top'.$type_engine.' from  price_region  where region_id =:id';
            $all_price = Yii::$app->db->createCommand($q)->bindParam(':id', $region_id)->queryOne();
            if ( ! is_array($all_price) || count($all_price) == 0 ) {
                $q = 'select '.$type_price.$type_engine.',price_parent'.$type_engine.',price_top'.$type_engine;
                $q .=' from price_country  where country_id = 1';
                $all_price = Yii::$app->db->createCommand($q)->bindParam(':id', $region_id)->queryOne();
                $price_region = $all_price['price_region'.$type_engine];
            }else{
                $price_region = $all_price['price'.$type_engine];
            }
            $parent = self::getParent($category_id);
            if ($category_id === 0) {
                $price_region += $all_price['price_top'.$type_engine];
            } elseif ($parent == 0 ) {
                $price_region += $all_price['price_parent'.$type_engine];
            }
            return $price_region;
        }
    }

    public static function getAllPriceRegion($region_id, $category_id, $type_engine='')
    {
        $field_price = 'price_region'.$type_engine;
        $price_region = self::getPriceRegion($region_id, 'price_region', $category_id,$type_engine);
        $price_category = ($category_id == 0) ? self::getPriceAllCategory($field_price) : self::getPriceCategory($category_id,$type_engine);
        return $price_region + $price_category;
    }

    public static function getParent($id)
    {
        $q = 'select parent_id from category  where id = :id';
        $parent = Yii::$app->db->createCommand($q)->bindParam(':id', $id)->queryScalar();
        if ($parent === false) return 1;
        else return $parent;

    }

    public static function getPriceCity( $city_id, $category_id, $type_engine='')
    {
        $category_id = (int) $category_id;
        $field_price = 'price'.$type_engine;
        $q = 'select '.$field_price.' from price_city  where city_id =:id and category_id='.$category_id;
        $target = Yii::$app->db->createCommand($q)->bindParam(':id', $city_id)->queryOne();
        if ( is_array($target) && count($target)>0 ){
            return $target[$field_price];
        }

        $modelCity = City::findOne(['city_id'=>$city_id]);
        if ($modelCity == null) {
            return  0;
        }

        $field_price_city = 'price_city';
        $region_id = $modelCity->region_id;
        $price_region = self::getPriceRegion($region_id,$field_price_city,$category_id, $type_engine);

        if ($price_region){
            return $price_region;
        }
    }

    public static function getAllPriceCity($city_id, $category_id, $type_engine = '')
    {
        $price_city = self::getPriceCity($city_id, $category_id,$type_engine);
        $price_category = ($category_id == 0) ? self::getPriceAllCategory('price_city'.$type_engine) : self::getPriceCategory($category_id,$type_engine);
        return $price_city + $price_category;
    }

    public static function getPriceCountry($country_id, $type_price)
    {
        $cache_id = 'price_country_'.$country_id.'_'.$type_price;
        $data = Yii::$app->cache->get($cache_id);
        if ($data && Yii::$app->params['is_cache']) return $data;

        $q = 'select * from price_country  where country_id =:id ';
        $target = Yii::$app->db->createCommand($q)->bindParam(':id', $country_id)->queryOne();
        Yii::$app->cache->set($cache_id,$target,6000);
        return $target;
    }

    public static function getAllPriceCountry( $category_id, $type_engine = '')
    {
        $all_price = self::getPriceCountry(1,'price'.$type_engine);
        $price_country = $all_price['price'.$type_engine];
        $parent = self::getParent($category_id);
        if ($category_id == 0) {
            $price_country += $all_price['price_top_country'.$type_engine];
        } elseif ($parent == 0 ) {
            $price_country += $all_price['price_parent_country'.$type_engine];
        }

        $price_category = ($category_id == 0) ? self::getPriceAllCategory('price'.$type_engine) : self::getPriceCategory($category_id,$type_engine);
        return $price_country + $price_category;
    }

    public static function getPriceOne( $type,$type_id,$category_id, $type_engine = '')
    {
        switch ($type){
            case 'country' : return self::getAllPriceCountry( $category_id,$type_engine);
            case 'region' : return self::getAllPriceRegion( $type_id,$category_id,$type_engine);
            default : return self::getAllPriceCity( $type_id,$category_id,$type_engine);
        }
    }

    public static function getFreePublish( $vacancy_id, $category_id , $city_id, $region_id, $type_engine = '' )
    {
        $arr = [];
        if (empty($category_id) ) $category_id = [0];
        if (is_null($city_id) ) $city_id = 0;
        if (is_null($region_id) ) $region_id = 0;
        if ( $city_id > 0){
            $arr['city'] = PriceCountry::getFreeTargetTop($vacancy_id,'city',$city_id,$category_id,$type_engine);
            $modelCity = City::findOne(['city_id'=>$city_id]);
            if ($modelCity == null) {
                Yii::$app->session->setFlash('warning', Yii::t('app', "Город отсутствует. ID=".$city_id));
            }else{
                $region_id = $modelCity->region_id;
                if ( $region_id > 0){
                    $arr['region'] = PriceCountry::getFreeTargetTop($vacancy_id,'region',$region_id,$category_id, $type_engine);
                }
            }
        }elseif ($region_id > 0){
            $arr['region'] = PriceCountry::getFreeTargetTop($vacancy_id,'region',$region_id,$category_id, $type_engine);
        }

        $arr['country'] = PriceCountry::getFreeTargetTop($vacancy_id,'country',1,$category_id, $type_engine);
        $res = [];
        if (count($arr)>0)
            foreach ($arr as $key=>$value){
                if (is_array($value)) {
                    foreach ($value as $key1=>$val)
                        if ($key1 == 0) $res[$key][] = ['id' => 0,'name' => 'все категории', 'price' => $val];
                        else{
                            $model = Category::findOne($key1);
                            $res[$key][] = ['id' => $key1, 'name' => $model['ru'], 'price' => $val];
                        }
                } else $res[$key][] = ['id' => 0, 'name' => 0, 'price' => 0];
            }
        return $res;
    }


}