<?php

namespace frontend\components\interkassa;

use Yii;
use yii\base\Object;
use yii\base\Exception;
use frontend\components\interkassa\api\Api;
use frontend\components\interkassa\api\WithdrawForm;

class Merchant extends Object {

    public $ik_co_id;
    public $ik_cur;
    public $secret_key;
    private $url = 'https://sci.interkassa.com/';

    public function payment($params) {

        $params['ik_co_id'] = $this->ik_co_id;
        $params['ik_cur'] = $this->ik_cur;
        $params['ik_sign'] = $this->ecp_generate($params);

        $url = $this->url . '?' . http_build_query($params);

        Yii::$app->user->setReturnUrl(Yii::$app->request->getUrl());
        return Yii::$app->response->redirect($url);
    }

    private function ecp_generate($params) {

        unset($params['ik_sign']);

        //удаляем из данных строку подписи
        ksort($params, SORT_STRING); // сортируем по ключам в алфавитном порядке элементы массива

        array_push($params, $this->secret_key); // добавляем в конец массива "секретный ключ"

        $signString = implode(':', $params); // конкатенируем значения через символ ":"

        $sign = base64_encode(md5($signString, true)); // берем MD5 хэш в бинарном виде по
        //сформированной строке и кодируем в BASE64
        return $sign; // возвращаем результат
    }

}
