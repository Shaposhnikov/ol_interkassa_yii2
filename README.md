# ol_interkassa_yii2
Компонент для оплаты товаров с помощью Интеркассы для Yii2

В папке components Вашего приложения (если ее нет - создаем) создаем папку intercass и распологаем там содержимое архива

# Конфигурация компонента (прописываем в файле config.php в разделе components)

```

```

# Пример работы с компонентом
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

        $session = Yii::$app->session;
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

    public function successCallback($ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no) {

        $session = Yii::$app->session;
        $intercassa = Yii::$app->get('interkassa');
        $model = $this->loadModel($ik_pm_no);

        if ($intercassa->ik_co_id == $ik_co_id && $model->price == $ik_am) {

            if (Yii::$app->user->isGuest && $id = \common\models\User::createUser()) {

                $user_id = $id;
            } else {

                $user_id = Yii::$app->user->identity->id;
            }

            $model->updateAttributes(['status' => Invoice::STATUS_SUCCESS, 'user_id' => $user_id]);
            $session->set('invoice_id', $model->id);
            return $this->redirect('/clients/success-pay/');
        } else {

            throw new NotFoundHttpException('Что-то пошло не так. Если это уже не первый раз, свяжитесь с техподдержкой и мы обязательно Вам поможем.');
        }
    }

    public function resultCallback($ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no) {

        switch ($ik_inv_st) {
            case 'new': return $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_NEW]);
                break;
            case 'waitAccept': return $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_PENDING]);
                break;
            case 'process': return $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_PROCESS]);
                break;
            case 'success': return $this->successCallback($ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no);
                break;
            case 'canceled': return $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_CANCELED]);
                break;
            case 'fail': return $this->failCallback($ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no);
                break;
        }
    }

    public function failCallback($ik_co_id, $ik_am, $ik_inv_st, $ik_pm_no) {
        $model = $this->loadModel($ik_pm_no);
        $session = Yii::$app->session;
        $session->set('error_pay', 'yes');

        if ($model->status != Invoice::STATUS_FAIL && $model->status != Invoice::STATUS_SUCCESS) {
            $model->updateAttributes(['status' => Invoice::STATUS_FAIL]);

            throw new NotFoundHttpException('При оплате произошла ошибка! Деньги не были списаны с вашего счета. Для повтроной попытки перейдите в <a href="/cart">корзину</a> и попробуйте оплатить снова');
        } else {
            throw new NotFoundHttpException('При оплате произошла ошибка! Деньги не были списаны с вашего счета. Для повтроной попытки перейдите в <a href="/cart">корзину</a> и попробуйте оплатить снова');
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