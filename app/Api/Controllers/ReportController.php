<?php
namespace Api\Controllers;

use Api\Model\Report;
use Api\Model\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportController extends BaseController {
    protected $tokenClass;

    public function __construct()
    {
        $this->middleware('jwt.auth');
    }



    public function Store(){
        $post_data = Input::all();
        if(isset($post_data['LicenceKey'])){unset($post_data['LicenceKey']);};
        if(isset($post_data['CompanyName'])){unset($post_data['CompanyName']);};
        if(isset($post_data['LoginType'])){unset($post_data['LoginType']);};

        $CompanyID = User::get_companyID();
        $rules['Name'] = 'required|unique:tblReport,Name,NULL,ReportID,CompanyID,' . $CompanyID;;

        $verifier = App::make('validation.presence');
        $verifier->setConnection('neon_report');

        $validator = Validator::make($post_data, $rules);
        $validator->setPresenceVerifier($verifier);

        if ($validator->fails()) {
            return generateResponse($validator->errors(),true);
        }
        try {
            $insertdata = array();
            $insertdata['CompanyID'] = $CompanyID;
            $insertdata['Name'] = $post_data['Name'];
            $insertdata['Settings'] = json_encode($post_data);
            $insertdata['CreatedBy'] = User::get_user_full_name();
            $insertdata['created_at'] = get_currenttime();
            $Report = Report::create($insertdata);

            return generateResponse('Report added successfully',false,false,$Report);
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }
    public function Delete($ReportID){
        try {
            if (intval($ReportID) > 0) {
                if (!Report::checkForeignKeyById($ReportID)) {
                    try {
                        DB::beginTransaction();
                        $result = Report::find($ReportID)->delete();
                        DB::commit();
                        if ($result) {
                            return generateResponse('Report Successfully Deleted');
                        } else {
                            return generateResponse('Problem Deleting Report.',true,true);
                        }
                    } catch (\Exception $ex) {
                        return generateResponse('Report is in Use, You cant delete this Report.',true,true);
                    }
                } else {
                    return generateResponse('Report is in Use, You cant delete this Report.',true,true);
                }
            } else {
                return generateResponse('Provide Valid Integer Value.',true,true);
            }
        } catch (\Exception $e) {
            Log::info($e);
            return $this->response->errorInternal('Internal Server');
        }
    }

    public function Update($ReportID){
        if ($ReportID > 0) {
            $post_data = Input::all();
            $CompanyID = User::get_companyID();
            if(isset($post_data['LicenceKey'])){unset($post_data['LicenceKey']);};
            if(isset($post_data['CompanyName'])){unset($post_data['CompanyName']);};
            if(isset($post_data['LoginType'])){unset($post_data['LoginType']);};
            $rules['Name'] = 'required|unique:tblReport,Name,' . $ReportID . ',ReportID,CompanyID,' . $CompanyID;


            $verifier = App::make('validation.presence');
            $verifier->setConnection('neon_report');

            $validator = Validator::make($post_data, $rules);
            $validator->setPresenceVerifier($verifier);
            if ($validator->fails()) {
                return generateResponse($validator->errors(),true);
            }
            try {

                try {
                    $Report = Report::findOrFail($ReportID);
                } catch (\Exception $e) {
                    Log::info($e);
                    return generateResponse('Report not found.',true,true);
                }
                $updatedata = array();
                $updatedata['Name'] = $post_data['Name'];
                $updatedata['Settings'] = json_encode($post_data);
                $updatedata['UpdatedBy'] = User::get_user_full_name();
                $Report->update($updatedata);

                return generateResponse('Report updated successfully');
            } catch (\Exception $e) {
                Log::info($e);
                return $this->response->errorInternal('Internal Server');
            }
        } else {
            return generateResponse('Provide Valid Integer Value.',true,true);
        }
    }



}