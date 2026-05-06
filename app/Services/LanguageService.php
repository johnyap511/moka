<?php namespace App\Services;

/**
 *
 */
class LanguageService
{
    public static function getLanguage(){
        return session('app_language');
    }

    public static function setLanguage($language){
        session(['app_language' => $language]);
        \App::setLocale($language);
    }
}
