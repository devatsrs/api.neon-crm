<?php
namespace Api\Model;

use Illuminate\Support\Facades\Log;

class TicketImportRuleCondition extends \Eloquent {

    protected $guarded = array("TicketImportRuleConditionID");
    protected $table = 'tblTicketImportRuleCondition';
    protected $primaryKey = "TicketImportRuleConditionID";

    var $field;
    var $operand;
    var $value;

    const IS = 'is';
    const IS_NOT = 'is_not';
    const CONTAINS = 'contains';
    const DOES_NOT_CONTAIN = 'does_not_contain';
    const START_WITH = 'start_with';
    const END_WITH = 'end_with';


    public function operand( $operand ) {
        $this->operand = trim($operand);
        return $this;
    }
    public static function field($field){
        $ins = new static;
        $ins->field = trim($field);
        return $ins;
    }

    public function value($value) {
        $this->value = trim($value);
        return $this;
    }

    public function check() {

        if(empty($this->operand)){
            return false;
        }

        Log::info("TicketImportRuleCondition::chech()");
        Log::info("operand"  . $this->operand);
        Log::info("field"  . $this->field);
        Log::info("value"  . $this->value);

        switch ($this->operand) {

            case self::IS:
                $values = explode(',',$this->value);
                if (in_array($this->field ,$values)){
                    Log::info("is compare ");

                    return true;
                }
                break;

            case self::IS_NOT:
                $values = explode(',',$this->value);
                if (!in_array($this->field ,$values)){
                    Log::info("is  not compare ");
                    return true;
                }
                break;

            case self::CONTAINS:
                $values = explode(',',$this->value);
                foreach($values as $value) {
                    if (strpos($this->field, $value) !== false) {
                        Log::info("contains compare ");
                        return true;
                    }
                }
                break;

            case self::DOES_NOT_CONTAIN:

                $values = explode(',',$this->value);
                $cnt = 0;
                foreach($values as $value) {
                    if (strpos($this->field, $value) == false) {
                        $cnt++;
                    }
                }
                if($cnt == count($values)){
                    Log::info("doesnt contains compare ");
                    return true;
                }
                break;

            case self::START_WITH:

                $length = strlen($this->value);
                if ( substr($this->field, 0, $length) === $this->value ) {
                    Log::info("start with compare ");
                    return true;
                }
                break;

            case self::END_WITH:

                $length = strlen($this->value);
                if ($length == 0) {
                    Log::info("end with compare ");
                    return true;
                }
                if ( substr($this->field, -$length) === $this->value ) {
                    Log::info("end with compare ");
                    return true;
                }
                break;

        }

        return false;

    }

}
