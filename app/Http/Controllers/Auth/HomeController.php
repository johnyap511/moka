<?php

namespace App\Http\Controllers\Auth;

use App\OtherModel\PropertyEstimate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getEstimate(Request $request){
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|max:250',
            'email' => 'required|email|max:150',
            'phone' => 'required|numeric',
            'bedroom' => 'required|string',
            'type' => 'required|string',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $formData = $request->only('address', 'email', 'phone', 'bedroom','type');
        $formData['bedroom'] = str_replace(' Bedroom', '', $formData['bedroom']);
        PropertyEstimate::create($formData);

        return back()->with("success", "Thank you for interested to work with HomeMoka, We will contact you as soon as possible!");
    }



    /**
     * @param $lang
     * @return \Illuminate\Http\RedirectResponse
     */

    public function setLanguage($lang){
        \App\Services\LanguageService::setLanguage($lang);
        return redirect()->back();
    }

}
