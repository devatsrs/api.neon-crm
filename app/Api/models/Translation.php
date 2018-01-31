<?php

namespace Api\Model;
use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class Translation extends \Eloquent {
	
	protected $guarded = array('TranslationID');

    protected $table = 'tblTranslation';

    protected  $primaryKey = "TranslationID";

	public static $enable_cache = false;
	public static $default_lang_id = 43; // English
	public static $default_lang_ISOcode = "en"; // English

    public static $cache = array(
    "language_dropdown1_cache",   // Country => Country
    "language_cache",    // all records in obj
);

    public static function getLanguageDropdownList(){

        if (self::$enable_cache && Cache::has('language_dropdown1_cache')) {
            //check if the cache has already the ```user_defaults``` item
            $admin_defaults = Cache::get('language_dropdown1_cache');
            //get the admin defaults
            self::$cache['language_dropdown1_cache'] = $admin_defaults['language_dropdown1_cache'];
        } else {
            //if the cache doesn't have it yet
            $dd = Translation::join('tblLanguage', 'tblLanguage.LanguageID', '=', 'tblTranslation.LanguageID')
                            ->whereRaw('tblLanguage.LanguageID=tblTranslation.LanguageID')
                            ->select("tblLanguage.ISOCode", "tblTranslation.Language")->get();

            $dropdown = array();
            foreach ($dd as $key => $value) {
                $dropdown[$value->ISOCode] = $value->Language;
            }
            self::$cache['language_dropdown1_cache'] = $dropdown;
                //cache the database results so we won't need to fetch them again for 10 minutes at least
            Cache::forever('language_dropdown1_cache', array('language_dropdown1_cache' => self::$cache['language_dropdown1_cache']));

        }

        return self::$cache['language_dropdown1_cache'];
    }

    public static function getLanguageDropdownWithFlagList(){

        if (self::$enable_cache && Cache::has('languageflag_dropdown1_cache')) {
            //check if the cache has already the ```user_defaults``` item
            $admin_defaults = Cache::get('languageflag_dropdown1_cache');
            //get the admin defaults
            self::$cache['languageflag_dropdown1_cache'] = $admin_defaults['languageflag_dropdown1_cache'];
        } else {
            //if the cache doesn't have it yet
            $dd = Translation::join('tblLanguage', 'tblLanguage.LanguageID', '=', 'tblTranslation.LanguageID')
                ->whereRaw('tblLanguage.LanguageID=tblTranslation.LanguageID')
                ->select("tblLanguage.ISOCode", "tblTranslation.Language", "tblLanguage.flag")->get();

            $dropdown = array();
            foreach ($dd as $key => $value) {
                $dropdown[$value->ISOCode] = ["languageName"=>$value->Language, "languageFlag"=>$value->flag];
            }
            self::$cache['languageflag_dropdown1_cache'] = $dropdown;
            //cache the database results so we won't need to fetch them again for 10 minutes at least
            Cache::forever('languageflag_dropdown1_cache', array('languageflag_dropdown1_cache' => self::$cache['languageflag_dropdown1_cache']));

        }

        return self::$cache['languageflag_dropdown1_cache'];
    }
    public static function getLanguageDropdownIdList($select=0){
        $dropdown = Translation::join('tblLanguage', 'tblLanguage.LanguageID', '=', 'tblTranslation.LanguageID')
            ->whereRaw('tblLanguage.LanguageID=tblTranslation.LanguageID')
            ->select("tblLanguage.LanguageID", "tblTranslation.Language")->lists("Language", "LanguageID");
        if($select==1) {
            $dropdown = array("" => "Select") + $dropdown;
        }
        return $dropdown;
    }
}