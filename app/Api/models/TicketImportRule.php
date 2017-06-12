<?php
namespace Api\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;


class TicketImportRule extends \Eloquent
{

    protected $table = "tblTicketImportRule";
    protected $primaryKey = "TicketImportRuleID";
    protected $guarded = array("TicketImportRuleID");

    const MATCH_ANY = 1;
    const MATCH_ALL = 2;

    var $operation_log = array();

    public static function check($CompanyID, $TicketData)
    {
        $TicketImportRules = TicketImportRule::where(["CompanyID" => $CompanyID, "Status" => 1])->get();
        $log = array();
        if (count($TicketImportRules) > 0) {

            Log::info("TicketImportRules " . count($TicketImportRules));
            Log::info($TicketImportRules);

            //rule loop
            foreach ($TicketImportRules as $TicketImportRule) {
                $TicketImportRuleID = $TicketImportRule["TicketImportRuleID"];
                $Match = $TicketImportRule["Match"];
                $total_rule_matches = 0;


                //condition loop
                $TicketImportRuleConditions = TicketImportRuleCondition::where(["TicketImportRuleID" => $TicketImportRuleID])->orderby("Order")->get();
                if (count($TicketImportRuleConditions) > 0) {

                    Log::info("TicketImportRuleConditions " . count($TicketImportRuleConditions));
                    Log::info($TicketImportRuleConditions);

                    foreach ($TicketImportRuleConditions as $TicketImportRuleCondition) {

                        $TicketImportRuleConditionTypeID = $TicketImportRuleCondition["TicketImportRuleConditionTypeID"];
                        $Operand = $TicketImportRuleCondition["Operand"];
                        $Value = $TicketImportRuleCondition["Value"];

                        Log::info("Operand " . $Operand);
                        Log::info("Value " . $Value);
                        Log::info("TicketImportRuleConditionTypeID " . $TicketImportRuleConditionTypeID);

                        if ($TicketImportRuleConditionTypeID > 0 && (new TicketImportRuleConditionType())->validate($TicketData,$TicketImportRuleConditionTypeID,$Operand,$Value)) {
                            $total_rule_matches++;
                            Log::info("total_rule_matches " . $total_rule_matches);
                            if ($Match == self::MATCH_ANY) {
                                Log::info("MATCH_ANY break");
                                break;
                            }

                        }

                    }
                }

                Log::info("Match " . $Match);
                Log::info("total_rule_matches " . $total_rule_matches);
                // condition match check
                if (($Match == self::MATCH_ANY && $total_rule_matches == 1) || ($Match == self::MATCH_ALL && $total_rule_matches == count($TicketImportRuleConditions))) {

                    $log_ = (new TicketImportRuleActionType())->doActions($TicketImportRuleID, $TicketData);
                    if(is_array($log_)){
                        $log = array_merge($log,$log_);
                    }
                }

                if(in_array(TicketImportRuleActionType::DELETE_TICKET,$log)){
                    return $log;
                }

            }// $TicketImportRules loop
        }

        return $log;
    }
}