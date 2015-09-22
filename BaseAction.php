<?php

namespace frontend\components\interkassa;

use yii\base\Action;
use yii\base\InvalidConfigException;

class BaseAction extends Action {

    public $merchant = 'interkassa';
    public $callback;

    /**
     * @param Merchant $merchant Merchant.
     * @param $nInvId
     * @param $nOutSum
     * @param $shp
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    protected function callback($merchant, $ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no) {
        if (!is_callable($this->callback)) {
            throw new InvalidConfigException('"' . get_class($this) . '::callback" should be a valid callback.');
        }
        $response = call_user_func($this->callback, $merchant, $ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no);
        return $response;
    }

}
