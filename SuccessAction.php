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

            return $this->callback($_REQUEST['ik_co_id'], $_REQUEST['ik_am'], $_REQUEST['ik_inv_st'], $_REQUEST['ik_pm_no']);
        }

        throw new BadRequestHttpException;
    }

}
