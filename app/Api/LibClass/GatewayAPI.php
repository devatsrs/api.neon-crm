<?php
namespace App;


class GatewayAPI extends \Eloquent {
	protected $fillable = [];

    private static $gateway_class = array('Sippy');

    public static function GatewayMethod($classname,$method,$param=array()){
        if(in_array($classname,self::$gateway_class)){
                $class =  new $classname;
               return $class->$method($param);
        }
    }
    public static function getSetting($CompanyGatewayID,$gatewayname){
        $gatewayid = Gateway::getGatewayID($gatewayname);
        if($gatewayid >0){
            $companysetting =  CompanyGateway::getCompanyGatewayConfig($CompanyGatewayID);
           return (array)json_decode($companysetting);
        }
    }


}