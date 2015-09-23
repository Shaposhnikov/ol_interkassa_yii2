# Компонент для оплаты товаров с помощью Interkassa для Yii2


В папке components Вашего приложения (если ее нет - создаем) создаем папку intercass и распологаем там содержимое архива

### Конфигурация компонента (прописываем в файле config.php в разделе components)

```
   'components' => [
        
        .............        

        'interkassa' => [
            'class' => 'frontend\components\interkassa\Merchant',
            'ik_co_id' => '', // id кассы 
            'ik_cur' => 'RUB', // валюта
            'secret_key' => '', // секретный ключ для цифровой подписи
        ],

        .............
        
    ],
```

### Пример работы с компонентом
```
<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use frontend\models\Invoice;
use yii\web\BadRequestHttpException;

class InterkassaController extends Controller {

    public function actionInvoice() {

        $model = new Invoice(); // модель счета фактуры, может называться как угодно, служит для работы с таблицей, в которой Вы храните счета фактуры или заказы или т.п. вещи

        $model->amount = сумма к оплате; // сумма к оплате которая приходит в контроллер каким либо реализуемым Вам способом

        if ($model->save()) {

            $merchant = Yii::$app->get('interkassa'); получаем компонент интеркассы
            
            // параметры, которые будут переданы в Интеркассу, с полным их перечнем можно ознакомиться в документации к Интеркассе
            $params = [
                'ik_pm_no' => 'id платежа',
                'ik_am' => 'сумма к оплате',
                'ik_desc' => 'комментарий',
                'ik_cli' => 'email плательщика',
            ];

            return $merchant->payment($params);
        } else {

            throw new NotFoundHttpException('Что-то пошло не так...');
        }
    }

    /**
     * @inheritdoc
     */
    public function actions() {
        return [
            'result' => [
                'class' => 'frontend\components\interkassa\ResultAction',
                'callback' => [$this, 'resultCallback'],
            ],
            'success' => [
                'class' => 'frontend\components\interkassa\SuccessAction',
                'callback' => [$this, 'successCallback'],
            ],
            'fail' => [
                'class' => 'frontend\components\interkassa\FailAction',
                'callback' => [$this, 'failCallback'],
            ],
        ];
    }

    public function successCallback($merchant, $ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no) {

        $model = $this->loadModel($ik_pm_no);

        if ($merchant->ik_co_id == $ik_co_id && $model->price == $ik_am) {

            $model->updateAttributes(['status' => Invoice::STATUS_SUCCESS]);
            return $this->redirect('/clients/success-pay/');
        } else {

            throw new NotFoundHttpException('Что-то пошло не так...');
        }
    }

    public function resultCallback($merchant, $ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no) {

        switch ($ik_inv_st) {
            case 'new': return $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_NEW]);
                break;
            case 'waitAccept': return $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_PENDING]);
                break;
            case 'process': return $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_PROCESS]);
                break;
            case 'success': return $this->successCallback($merchant, $ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no);
                break;
            case 'canceled': return $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_CANCELED]);
                break;
            case 'fail': return $this->failCallback($merchant, $ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no);
                break;
        }
    }

    public function failCallback($merchant, $ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no) {
        $model = $this->loadModel($ik_pm_no);

        if ($model->status != Invoice::STATUS_FAIL && $model->status != Invoice::STATUS_SUCCESS) {
            $model->updateAttributes(['status' => Invoice::STATUS_FAIL]);

            throw new NotFoundHttpException('При оплате произошла ошибка!');
        } else {
            throw new NotFoundHttpException('При оплате произошла ошибка!');
        }
    }

    /**
     * @param integer $id
     * @return Invoice
     * @throws \yii\web\BadRequestHttpException
     */
    protected function loadModel($id) {
        $model = Invoice::findOne($id);
        if ($model === null) {
            throw new BadRequestHttpException;
        }
        return $model;
    }

}
```
