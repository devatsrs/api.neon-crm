<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class Lead extends \Eloquent {

    protected $guarded = array();

    protected $table = 'tblAccount';

    protected  $primaryKey = "AccountID";


    public static $rules = array(
        'Owner' =>      'required',
        'CompanyID' =>  'required',
        'AccountName' => 'required|unique:tblAccount,AccountName',
        'FirstName' =>  'required',
        'LastName' =>  'required',
        'LastName' =>  'required',
    );
    public static function getRecentLeads($limit){
        $companyID  = User::get_companyID();
        $leads = Account::Where(["AccountType"=> 0,"CompanyID"=>$companyID,"Status"=>1])
            ->orderBy("tblAccount.AccountID", "desc")
            ->take($limit)
            ->get();

        return $leads;

    }

    public static function getLeadOwnersByRole(){
        $companyID = User::get_companyID();
        if(User::is('AccountManager')){
            $UserID = User::get_userID();
            $lead_owners = DB::table('tblAccount')->where([ "AccountType" => 0, "CompanyID" => $companyID, "Status" => 1])->orderBy('FirstName', 'asc')->get();
        }
        else{
            $lead_owners = DB::table('tblAccount')->where(["AccountType" => 0, "CompanyID" => $companyID, "Status" => 1])->orderBy('FirstName', 'asc')->get();
        }

        return $lead_owners;
    }

    public static  function getLeadList($data=[]){
        if(User::is('AccountManager')){
            $data['Owner'] = User::get_userID();
        }
        if(User::is_admin() && isset($data['UserID'])){
            $data['Owner'] = $data['UserID'];
        }

        $data['Status'] = 1;
        if(!isset($data['AccountType'])) {
            $data['AccountType'] = 0;
        }
        $data['CompanyID']=User::get_companyID();
        $row = Lead::where($data)->select(array('AccountName', 'AccountID'))->orderBy('AccountName')->lists('AccountName', 'AccountID');
        if(!empty($row)){
            $row = array(""=> "Select a Lead")+$row;
        }
        return $row;
    }

}