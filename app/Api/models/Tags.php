<?php
namespace Api\Model;

use Illuminate\Database\Eloquent\Model;
class Tags extends \Eloquent {
    protected $guarded = array("TagID");

    protected $table = 'tblTags';

    protected  $primaryKey = "TagID";
    const  Account_tag = 1;
    const  Lead_tag = 2;
    const  Opportunity_tag = 3;
    public static function getTagsArray($type = 1){
        $tags = Tags::where(array('CompanyID'=>User::get_companyID(),'TagType'=>$type))->get(array("TagName"));
        if(!empty($tags)){
            $tagsname = [];
            foreach($tags as $tag){
                $tagsname[] = $tag->TagName;
            }
            return $tagsname;
        }
    }
    public static function insertNewTags($data = []){
        if(count($data)>0){
            $newTags = array_diff(explode(',', $data['tags']), Tags::getTagsArray($data['TagType']));
            if (count($newTags) > 0) {
                foreach ($newTags as $tag) {
                    Tags::create(array('TagName' => $tag, 'CompanyID' => User::get_companyID(), 'TagType' => $data['TagType']));
                }
            }
        }

    }

}