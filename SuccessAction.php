<?php

namespace frontend\components\interkassa;

use Yii;
use yii\web\BadRequestHttpException;

class SuccessAction extends BaseAction {

    /**
     * Runs the action.
     */
    public function run() {

        if (!isset($_REQUEST['ik_co_id'], $_REQUEST['ik_am'], $_REQUEST['ik_inv_st'], $_REQUEST['ik_pm_no'])) {

            throw new BadRequestHttpException;
        } else {
            
            $merchant = Yii::$app->get($this->merchant);

            return $this->callback($merchant, $_REQUEST['ik_co_id'], $_REQUEST['ik_am'], $_REQUEST['ik_inv_st'], $_REQUEST['ik_pm_no']);
        }

        throw new BadRequestHttpException;
    }

}
