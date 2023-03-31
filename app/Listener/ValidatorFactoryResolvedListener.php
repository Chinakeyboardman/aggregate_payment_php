<?php


namespace App\Listener;

use App\Constants\CheckIdCard;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\Event\ValidatorFactoryResolved;
use function Symfony\Component\Translation\t;

/**
 * @Listener
 */
class ValidatorFactoryResolvedListener implements ListenerInterface
{

    public function listen(): array
    {
        return [
            ValidatorFactoryResolved::class,
        ];
    }

    /**
     * 银行卡的匹配方式
     * @param $card
     * @return false|int
     */
    public function bankCard($card){
        $pattern = '/^([1-9]{1})(\d{14}|\d{18})$/';
        return preg_match($pattern,$card);
    }


    public function iphone($number){
        $pattern = '/^1\d{10}$/';
        return preg_match($pattern,$number);
    }


    public function tel($number){
        $pattern = '/^([0-9]{3,4}-)?[0-9]{7,8}$/';
        return preg_match($pattern,$number);
    }

    public function process(object $event)
    {
        /**  @var ValidatorFactoryInterface $validatorFactory */
        $validatorFactory = $event->validatorFactory;

//        $validatorFactory->extend('account', function ($attribute, $value, $parameters, $validator) {
//            $data = $validator->getData(); //获取验证数据
//            switch ($data[$parameters[0]]){
//                case 1:
//                    if($this->bankCard($value)){
//                        return true;
//                    }
//                    $validator->setCustomMessages([$attribute.'.account' => '请输入正确的银行卡']);
//                    return false;
//                default:
//                    return true;
//            }
//        });

        $validatorFactory->extend('communication', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData(); //获取验证数据
            switch ($data[$parameters[0]]){
                case 1:
                    if($this->iphone($value)){
                        return true;
                    }
                    $validator->setCustomMessages([$attribute.'.communication' => '请输入正确的手机号']);
                    return false;
                case 3:
                    if($this->tel($value)){
                        return true;
                    }
                    $validator->setCustomMessages([$attribute.'.communication' => '请输入正确的固定电话']);
                    return false;
                default:
                    return true;
            }
        });

        $validatorFactory->extend('check_id_card', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData(); //获取验证数据
            if (!isset($parameters[0]) || !isset($data[$parameters[0]])){
                $type = 1;
            } else {
                $type = $data[$parameters[0]];
            }
            switch ($type){
                case 1:
                    $check = make(CheckIdCard::class);
                    if($check->isIdCard($value)){
                        return true;
                    }
                    return false;
                default:
                    return true;
            }
        });

        $validatorFactory->extend('check_sex', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData(); //获取验证数据
            $check = make(CheckIdCard::class);
            if($check->isIdCard($data[$parameters[0]])){
                if($check->getSexSign($data[$parameters[0]]) == $value){
                    return true;
                }
                return  false;
            }
            return true;
        });

        $validatorFactory->extend('iphone', function ($attribute, $value, $parameters, $validator) {
                    if($this->iphone($value)){
                        return true;
                    }
               return false;
        });

        $validatorFactory->replacer('iphone', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':iphone', $attribute, $message);
        });

        $validatorFactory->replacer('exists_type_model', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':exists_type_model', $attribute, $message);
        });

        $validatorFactory->replacer('exists_way_model', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':exists_way_model', $attribute, $message);
        });


        $validatorFactory->replacer('reception_police_order', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':reception_police_order', $attribute, $message);
        });

        $validatorFactory->replacer('account', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':account', $attribute, $message);
        });

        $validatorFactory->replacer('communication', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':communication', $attribute, $message);
        });

        $validatorFactory->replacer('check_id_card', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':checkIdCard', $attribute, $message);
        });

        $validatorFactory->replacer('check_sex', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':check_sex', $attribute, $message);
        });

        $validatorFactory->replacer('existence', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':existence', $attribute, $message);
        });
    }
}