@extends('auth.newTheme.layout')

@section('hide-footer', true)

@section('content')
<div class="home-banner-outer">
    @include('auth.newTheme.partials.header')
<script>
    // change header color on scroll
    let header = document.getElementById("main_header");


    header.classList.remove("bg-orange");
    window.addEventListener("scroll", ()=>{
        if(window.scrollY > 5){
            header.classList.add("bg-orange");
            header.style.boxShadow = "0px 0px 20px -3px rgba(0,0,0,0.1)";
        }
        else{
            header.classList.remove("bg-orange");
            header.style.boxShadow = "none";
        }
    })

    // rotate menu expand btn
    let menuExpand = (e) => {
        let expandIcon = e.querySelector(".fa-chevron-up");
        console.log(e)
        expandIcon.classList.toggle("active");
    }
</script>
<div>
    <div class="modal" id="loninModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/login" method="post">
                        <input type="hidden" name="_token" value="th6AHaoKd7sI2AYTMqsuZt5rRiW71ORmLj9FP5mJ">                        <input type="email" name="email" placeholder="Email or phone number" required>
                        <input type="password" name="password" placeholder="Enter your password" required>
                        <a href="/get/estimate#">
                            <p class="text-danger text-end" data-bs-toggle="modal" data-bs-target="#forgetPasswordModal">Forget Password ?</p>
                        </a>
                        <button type="submit" class="primary-btn w-100">Log In</button>
                    </form>
                    <div class="d-flex align-items-center gap-2 my-3">
                        <hr class="m-0 flex-grow-1">
                        <p class="m-0 text-center">Or</p>
                        <hr class="m-0 flex-grow-1">
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-2">
                        <a href="/login">
                            <button class="flex-grow-1 d-flex align-items-center justify-content-center gap-2 google-btn mb-2 mb-md-0">
                             <img src="{{ asset('new-theme23/images/icon-google.png') }}" alt="" srcset="" width="25px">
                            Continue With Google
                            </button>
                        </a>
                        <a href="/login">
                            <button class="flex-grow-1 d-flex align-items-center justify-content-center gap-2 facebook-btn">
                            <img src="{{ asset('new-theme23/images/icon-facebook.png') }}" alt="" srcset="" width="25px">
                            Continue With Facebook
                            </button>
                        </a>
                    </div>
                    <p class="mt-4 mb-3">Don't have account <a href="/get/estimate#" class="text-decoration-underline" data-bs-toggle="modal" data-bs-target="#signupModal"> Sign Up </a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="signupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sing Up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/register" method="post">
                        <input type="hidden" name="_token" value="th6AHaoKd7sI2AYTMqsuZt5rRiW71ORmLj9FP5mJ">                        <div class="phone-input-sec">
                            <div class="me-select">
                                <select name="country_code" required>
                                <option value="60">Malaysia (+60)</option>
<option value="93">Afghanistan (+93)</option>
<option value="358">Aland Islands (+358)</option>
<option value="355">Albania (+355)</option>
<option value="213">Algeria (+213)</option>
<option value="1684">American Samoa (+1684)</option>
<option value="376">Andorra (+376)</option>
<option value="244">Angola (+244)</option>
<option value="1264">Anguilla (+1264)</option>
<option value="1268">Antigua and Barbuda (+1268)</option>
<option value="54">Argentina (+54)</option>
<option value="374">Armenia (+374)</option>
<option value="297">Aruba (+297)</option>
<option value="61">Australia (+61)</option>
<option value="43">Austria (+43)</option>
<option value="994">Azerbaijan (+994)</option>
<option value="1242">Bahamas (+1242)</option>
<option value="973">Bahrain (+973)</option>
<option value="880">Bangladesh (+880)</option>
<option value="1246">Barbados (+1246)</option>
<option value="375">Belarus (+375)</option>
<option value="32">Belgium (+32)</option>
<option value="501">Belize (+501)</option>
<option value="229">Benin (+229)</option>
<option value="1441">Bermuda (+1441)</option>
<option value="975">Bhutan (+975)</option>
<option value="591">Bolivia (+591)</option>
<option value="599">Bonaire, Sint Eustatius and Saba (+599)</option>
<option value="387">Bosnia and Herzegovina (+387)</option>
<option value="267">Botswana (+267)</option>
<option value="55">Brazil (+55)</option>
<option value="246">British Indian Ocean Territory (+246)</option>
<option value="673">Brunei Darussalam (+673)</option>
<option value="359">Bulgaria (+359)</option>
<option value="226">Burkina Faso (+226)</option>
<option value="257">Burundi (+257)</option>
<option value="855">Cambodia (+855)</option>
<option value="237">Cameroon (+237)</option>
<option value="1">Canada (+1)</option>
<option value="238">Cape Verde (+238)</option>
<option value="1345">Cayman Islands (+1345)</option>
<option value="236">Central African Republic (+236)</option>
<option value="235">Chad (+235)</option>
<option value="56">Chile (+56)</option>
<option value="86">China (+86)</option>
<option value="61">Christmas Island (+61)</option>
<option value="672">Cocos (Keeling) Islands (+672)</option>
<option value="57">Colombia (+57)</option>
<option value="269">Comoros (+269)</option>
<option value="242">Congo (+242)</option>
<option value="242">Congo, the Democratic Republic of the (+242)</option>
<option value="682">Cook Islands (+682)</option>
<option value="506">Costa Rica (+506)</option>
<option value="225">Cote D'Ivoire (+225)</option>
<option value="385">Croatia (+385)</option>
<option value="53">Cuba (+53)</option>
<option value="599">Curacao (+599)</option>
<option value="357">Cyprus (+357)</option>
<option value="420">Czech Republic (+420)</option>
<option value="45">Denmark (+45)</option>
<option value="253">Djibouti (+253)</option>
<option value="1767">Dominica (+1767)</option>
<option value="1809">Dominican Republic (+1809)</option>
<option value="593">Ecuador (+593)</option>
<option value="20">Egypt (+20)</option>
<option value="503">El Salvador (+503)</option>
<option value="240">Equatorial Guinea (+240)</option>
<option value="291">Eritrea (+291)</option>
<option value="372">Estonia (+372)</option>
<option value="251">Ethiopia (+251)</option>
<option value="500">Falkland Islands (Malvinas) (+500)</option>
<option value="298">Faroe Islands (+298)</option>
<option value="679">Fiji (+679)</option>
<option value="358">Finland (+358)</option>
<option value="33">France (+33)</option>
<option value="594">French Guiana (+594)</option>
<option value="689">French Polynesia (+689)</option>
<option value="241">Gabon (+241)</option>
<option value="220">Gambia (+220)</option>
<option value="995">Georgia (+995)</option>
<option value="49">Germany (+49)</option>
<option value="233">Ghana (+233)</option>
<option value="350">Gibraltar (+350)</option>
<option value="30">Greece (+30)</option>
<option value="299">Greenland (+299)</option>
<option value="1473">Grenada (+1473)</option>
<option value="590">Guadeloupe (+590)</option>
<option value="1671">Guam (+1671)</option>
<option value="502">Guatemala (+502)</option>
<option value="44">Guernsey (+44)</option>
<option value="224">Guinea (+224)</option>
<option value="245">Guinea-Bissau (+245)</option>
<option value="592">Guyana (+592)</option>
<option value="509">Haiti (+509)</option>
<option value="39">Holy See (Vatican City State) (+39)</option>
<option value="504">Honduras (+504)</option>
<option value="852">Hong Kong (+852)</option>
<option value="36">Hungary (+36)</option>
<option value="354">Iceland (+354)</option>
<option value="91">India (+91)</option>
<option value="62">Indonesia (+62)</option>
<option value="98">Iran, Islamic Republic of (+98)</option>
<option value="964">Iraq (+964)</option>
<option value="353">Ireland (+353)</option>
<option value="44">Isle of Man (+44)</option>
<option value="972">Israel (+972)</option>
<option value="39">Italy (+39)</option>
<option value="1876">Jamaica (+1876)</option>
<option value="81">Japan (+81)</option>
<option value="44">Jersey (+44)</option>
<option value="962">Jordan (+962)</option>
<option value="7">Kazakhstan (+7)</option>
<option value="254">Kenya (+254)</option>
<option value="686">Kiribati (+686)</option>
<option value="850">Korea, Democratic People"s Republic of (+850)</option>
<option value="82">Korea, Republic of (+82)</option>
<option value="381">Kosovo (+381)</option>
<option value="965">Kuwait (+965)</option>
<option value="996">Kyrgyzstan (+996)</option>
<option value="856">Lao People's Democratic Republic (+856)</option>
<option value="371">Latvia (+371)</option>
<option value="961">Lebanon (+961)</option>
<option value="266">Lesotho (+266)</option>
<option value="231">Liberia (+231)</option>
<option value="218">Libyan Arab Jamahiriya (+218)</option>
<option value="423">Liechtenstein (+423)</option>
<option value="370">Lithuania (+370)</option>
<option value="352">Luxembourg (+352)</option>
<option value="853">Macao (+853)</option>
<option value="389">Macedonia, the Former Yugoslav Republic of (+389)</option>
<option value="261">Madagascar (+261)</option>
<option value="265">Malawi (+265)</option>
<option value="960">Maldives (+960)</option>
<option value="223">Mali (+223)</option>
<option value="356">Malta (+356)</option>
<option value="692">Marshall Islands (+692)</option>
<option value="596">Martinique (+596)</option>
<option value="222">Mauritania (+222)</option>
<option value="230">Mauritius (+230)</option>
<option value="269">Mayotte (+269)</option>
<option value="52">Mexico (+52)</option>
<option value="691">Micronesia, Federated States of (+691)</option>
<option value="373">Moldova, Republic of (+373)</option>
<option value="377">Monaco (+377)</option>
<option value="976">Mongolia (+976)</option>
<option value="382">Montenegro (+382)</option>
<option value="1664">Montserrat (+1664)</option>
<option value="212">Morocco (+212)</option>
<option value="258">Mozambique (+258)</option>
<option value="95">Myanmar (+95)</option>
<option value="264">Namibia (+264)</option>
<option value="674">Nauru (+674)</option>
<option value="977">Nepal (+977)</option>
<option value="31">Netherlands (+31)</option>
<option value="599">Netherlands Antilles (+599)</option>
<option value="687">New Caledonia (+687)</option>
<option value="64">New Zealand (+64)</option>
<option value="505">Nicaragua (+505)</option>
<option value="227">Niger (+227)</option>
<option value="234">Nigeria (+234)</option>
<option value="683">Niue (+683)</option>
<option value="672">Norfolk Island (+672)</option>
<option value="1670">Northern Mariana Islands (+1670)</option>
<option value="47">Norway (+47)</option>
<option value="968">Oman (+968)</option>
<option value="92">Pakistan (+92)</option>
<option value="680">Palau (+680)</option>
<option value="970">Palestinian Territory, Occupied (+970)</option>
<option value="507">Panama (+507)</option>
<option value="675">Papua New Guinea (+675)</option>
<option value="595">Paraguay (+595)</option>
<option value="51">Peru (+51)</option>
<option value="63">Philippines (+63)</option>
<option value="64">Pitcairn (+64)</option>
<option value="48">Poland (+48)</option>
<option value="351">Portugal (+351)</option>
<option value="1787">Puerto Rico (+1787)</option>
<option value="974">Qatar (+974)</option>
<option value="262">Reunion (+262)</option>
<option value="40">Romania (+40)</option>
<option value="70">Russian Federation (+70)</option>
<option value="250">Rwanda (+250)</option>
<option value="590">Saint Barthelemy (+590)</option>
<option value="290">Saint Helena (+290)</option>
<option value="1869">Saint Kitts and Nevis (+1869)</option>
<option value="1758">Saint Lucia (+1758)</option>
<option value="590">Saint Martin (+590)</option>
<option value="508">Saint Pierre and Miquelon (+508)</option>
<option value="1784">Saint Vincent and the Grenadines (+1784)</option>
<option value="684">Samoa (+684)</option>
<option value="378">San Marino (+378)</option>
<option value="239">Sao Tome and Principe (+239)</option>
<option value="966">Saudi Arabia (+966)</option>
<option value="221">Senegal (+221)</option>
<option value="381">Serbia (+381)</option>
<option value="381">Serbia and Montenegro (+381)</option>
<option value="248">Seychelles (+248)</option>
<option value="232">Sierra Leone (+232)</option>
<option value="65">Singapore (+65)</option>
<option value="1">Sint Maarten (+1)</option>
<option value="421">Slovakia (+421)</option>
<option value="386">Slovenia (+386)</option>
<option value="677">Solomon Islands (+677)</option>
<option value="252">Somalia (+252)</option>
<option value="27">South Africa (+27)</option>
<option value="500">South Georgia and the South Sandwich Islands (+500)</option>
<option value="211">South Sudan (+211)</option>
<option value="34">Spain (+34)</option>
<option value="94">Sri Lanka (+94)</option>
<option value="249">Sudan (+249)</option>
<option value="597">Suriname (+597)</option>
<option value="47">Svalbard and Jan Mayen (+47)</option>
<option value="268">Swaziland (+268)</option>
<option value="46">Sweden (+46)</option>
<option value="41">Switzerland (+41)</option>
<option value="963">Syrian Arab Republic (+963)</option>
<option value="886">Taiwan, Province of China (+886)</option>
<option value="992">Tajikistan (+992)</option>
<option value="255">Tanzania, United Republic of (+255)</option>
<option value="66">Thailand (+66)</option>
<option value="670">Timor-Leste (+670)</option>
<option value="228">Togo (+228)</option>
<option value="690">Tokelau (+690)</option>
<option value="676">Tonga (+676)</option>
<option value="1868">Trinidad and Tobago (+1868)</option>
<option value="216">Tunisia (+216)</option>
<option value="90">Turkey (+90)</option>
<option value="7370">Turkmenistan (+7370)</option>
<option value="1649">Turks and Caicos Islands (+1649)</option>
<option value="688">Tuvalu (+688)</option>
<option value="256">Uganda (+256)</option>
<option value="380">Ukraine (+380)</option>
<option value="971">United Arab Emirates (+971)</option>
<option value="44">United Kingdom (+44)</option>
<option value="1">United States (+1)</option>
<option value="1">United States Minor Outlying Islands (+1)</option>
<option value="598">Uruguay (+598)</option>
<option value="998">Uzbekistan (+998)</option>
<option value="678">Vanuatu (+678)</option>
<option value="58">Venezuela (+58)</option>
<option value="84">Viet Nam (+84)</option>
<option value="1284">Virgin Islands, British (+1284)</option>
<option value="1340">Virgin Islands, U.s. (+1340)</option>
<option value="681">Wallis and Futuna (+681)</option>
<option value="212">Western Sahara (+212)</option>
<option value="967">Yemen (+967)</option>
<option value="260">Zambia (+260)</option>
<option value="263">Zimbabwe (+263)</option>                                </select>
                            </div>
                            <input type="text" name="phone" placeholder="Enter phone number">
                        </div>

                        <input type="text" placeholder="Enter your name" name="name" required>
                        <input type="email" placeholder="Enter your email" name="email" required>
                        <input type="password" placeholder="Enter your password" name="password" required>
                        <button type="submit" class="primary-btn w-100">Continue</button>
                    </form>
                    <div class="d-flex align-items-center gap-2 my-3">
                        <hr class="m-0 flex-grow-1">
                        <p class="m-0 text-center">Or</p>
                        <hr class="m-0 flex-grow-1">
                    </div>
                    <div class="d-flex flex-column flex-md-row gap-2">
                        <a href="/login">   <button
                            class="flex-grow-1 d-flex align-items-center justify-content-center gap-2 google-btn mb-2 mb-md-0">
                              <img src="{{ asset('new-theme23/images/icon-google.png') }}" alt="" srcset="" width="25px">
                            Continue With Google
                        </button></a>
                        <a href="/login"> <button class="flex-grow-1 d-flex align-items-center justify-content-center gap-2 facebook-btn">
                             <img src="{{ asset('new-theme23/images/icon-facebook.png') }}" alt="" srcset="" width="25px">
                            Continue With Facebook</button></a>
                    </div>
                    <p class="my-4">Already have an account <a href="/get/estimate#" class="text-decoration-underline" data-bs-toggle="modal" data-bs-target="#loninModal"> Log in </a>
                    </p>
                    <button class="green-btn w-100 mb-3" data-bs-toggle="modal" data-bs-target="#signupOwnerModal">Sign Up as Owner</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="signupOwnerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sing Up as Owner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/register/owner" method="post">
                        <input type="hidden" name="_token" value="th6AHaoKd7sI2AYTMqsuZt5rRiW71ORmLj9FP5mJ">                    <div class="phone-input-sec">
                        <div class="me-select">
                            <select name="country_code" required>
                               <option value="60">Malaysia (+60)</option>
<option value="93">Afghanistan (+93)</option>
<option value="358">Aland Islands (+358)</option>
<option value="355">Albania (+355)</option>
<option value="213">Algeria (+213)</option>
<option value="1684">American Samoa (+1684)</option>
<option value="376">Andorra (+376)</option>
<option value="244">Angola (+244)</option>
<option value="1264">Anguilla (+1264)</option>
<option value="1268">Antigua and Barbuda (+1268)</option>
<option value="54">Argentina (+54)</option>
<option value="374">Armenia (+374)</option>
<option value="297">Aruba (+297)</option>
<option value="61">Australia (+61)</option>
<option value="43">Austria (+43)</option>
<option value="994">Azerbaijan (+994)</option>
<option value="1242">Bahamas (+1242)</option>
<option value="973">Bahrain (+973)</option>
<option value="880">Bangladesh (+880)</option>
<option value="1246">Barbados (+1246)</option>
<option value="375">Belarus (+375)</option>
<option value="32">Belgium (+32)</option>
<option value="501">Belize (+501)</option>
<option value="229">Benin (+229)</option>
<option value="1441">Bermuda (+1441)</option>
<option value="975">Bhutan (+975)</option>
<option value="591">Bolivia (+591)</option>
<option value="599">Bonaire, Sint Eustatius and Saba (+599)</option>
<option value="387">Bosnia and Herzegovina (+387)</option>
<option value="267">Botswana (+267)</option>
<option value="55">Brazil (+55)</option>
<option value="246">British Indian Ocean Territory (+246)</option>
<option value="673">Brunei Darussalam (+673)</option>
<option value="359">Bulgaria (+359)</option>
<option value="226">Burkina Faso (+226)</option>
<option value="257">Burundi (+257)</option>
<option value="855">Cambodia (+855)</option>
<option value="237">Cameroon (+237)</option>
<option value="1">Canada (+1)</option>
<option value="238">Cape Verde (+238)</option>
<option value="1345">Cayman Islands (+1345)</option>
<option value="236">Central African Republic (+236)</option>
<option value="235">Chad (+235)</option>
<option value="56">Chile (+56)</option>
<option value="86">China (+86)</option>
<option value="61">Christmas Island (+61)</option>
<option value="672">Cocos (Keeling) Islands (+672)</option>
<option value="57">Colombia (+57)</option>
<option value="269">Comoros (+269)</option>
<option value="242">Congo (+242)</option>
<option value="242">Congo, the Democratic Republic of the (+242)</option>
<option value="682">Cook Islands (+682)</option>
<option value="506">Costa Rica (+506)</option>
<option value="225">Cote D'Ivoire (+225)</option>
<option value="385">Croatia (+385)</option>
<option value="53">Cuba (+53)</option>
<option value="599">Curacao (+599)</option>
<option value="357">Cyprus (+357)</option>
<option value="420">Czech Republic (+420)</option>
<option value="45">Denmark (+45)</option>
<option value="253">Djibouti (+253)</option>
<option value="1767">Dominica (+1767)</option>
<option value="1809">Dominican Republic (+1809)</option>
<option value="593">Ecuador (+593)</option>
<option value="20">Egypt (+20)</option>
<option value="503">El Salvador (+503)</option>
<option value="240">Equatorial Guinea (+240)</option>
<option value="291">Eritrea (+291)</option>
<option value="372">Estonia (+372)</option>
<option value="251">Ethiopia (+251)</option>
<option value="500">Falkland Islands (Malvinas) (+500)</option>
<option value="298">Faroe Islands (+298)</option>
<option value="679">Fiji (+679)</option>
<option value="358">Finland (+358)</option>
<option value="33">France (+33)</option>
<option value="594">French Guiana (+594)</option>
<option value="689">French Polynesia (+689)</option>
<option value="241">Gabon (+241)</option>
<option value="220">Gambia (+220)</option>
<option value="995">Georgia (+995)</option>
<option value="49">Germany (+49)</option>
<option value="233">Ghana (+233)</option>
<option value="350">Gibraltar (+350)</option>
<option value="30">Greece (+30)</option>
<option value="299">Greenland (+299)</option>
<option value="1473">Grenada (+1473)</option>
<option value="590">Guadeloupe (+590)</option>
<option value="1671">Guam (+1671)</option>
<option value="502">Guatemala (+502)</option>
<option value="44">Guernsey (+44)</option>
<option value="224">Guinea (+224)</option>
<option value="245">Guinea-Bissau (+245)</option>
<option value="592">Guyana (+592)</option>
<option value="509">Haiti (+509)</option>
<option value="39">Holy See (Vatican City State) (+39)</option>
<option value="504">Honduras (+504)</option>
<option value="852">Hong Kong (+852)</option>
<option value="36">Hungary (+36)</option>
<option value="354">Iceland (+354)</option>
<option value="91">India (+91)</option>
<option value="62">Indonesia (+62)</option>
<option value="98">Iran, Islamic Republic of (+98)</option>
<option value="964">Iraq (+964)</option>
<option value="353">Ireland (+353)</option>
<option value="44">Isle of Man (+44)</option>
<option value="972">Israel (+972)</option>
<option value="39">Italy (+39)</option>
<option value="1876">Jamaica (+1876)</option>
<option value="81">Japan (+81)</option>
<option value="44">Jersey (+44)</option>
<option value="962">Jordan (+962)</option>
<option value="7">Kazakhstan (+7)</option>
<option value="254">Kenya (+254)</option>
<option value="686">Kiribati (+686)</option>
<option value="850">Korea, Democratic People"s Republic of (+850)</option>
<option value="82">Korea, Republic of (+82)</option>
<option value="381">Kosovo (+381)</option>
<option value="965">Kuwait (+965)</option>
<option value="996">Kyrgyzstan (+996)</option>
<option value="856">Lao People's Democratic Republic (+856)</option>
<option value="371">Latvia (+371)</option>
<option value="961">Lebanon (+961)</option>
<option value="266">Lesotho (+266)</option>
<option value="231">Liberia (+231)</option>
<option value="218">Libyan Arab Jamahiriya (+218)</option>
<option value="423">Liechtenstein (+423)</option>
<option value="370">Lithuania (+370)</option>
<option value="352">Luxembourg (+352)</option>
<option value="853">Macao (+853)</option>
<option value="389">Macedonia, the Former Yugoslav Republic of (+389)</option>
<option value="261">Madagascar (+261)</option>
<option value="265">Malawi (+265)</option>
<option value="960">Maldives (+960)</option>
<option value="223">Mali (+223)</option>
<option value="356">Malta (+356)</option>
<option value="692">Marshall Islands (+692)</option>
<option value="596">Martinique (+596)</option>
<option value="222">Mauritania (+222)</option>
<option value="230">Mauritius (+230)</option>
<option value="269">Mayotte (+269)</option>
<option value="52">Mexico (+52)</option>
<option value="691">Micronesia, Federated States of (+691)</option>
<option value="373">Moldova, Republic of (+373)</option>
<option value="377">Monaco (+377)</option>
<option value="976">Mongolia (+976)</option>
<option value="382">Montenegro (+382)</option>
<option value="1664">Montserrat (+1664)</option>
<option value="212">Morocco (+212)</option>
<option value="258">Mozambique (+258)</option>
<option value="95">Myanmar (+95)</option>
<option value="264">Namibia (+264)</option>
<option value="674">Nauru (+674)</option>
<option value="977">Nepal (+977)</option>
<option value="31">Netherlands (+31)</option>
<option value="599">Netherlands Antilles (+599)</option>
<option value="687">New Caledonia (+687)</option>
<option value="64">New Zealand (+64)</option>
<option value="505">Nicaragua (+505)</option>
<option value="227">Niger (+227)</option>
<option value="234">Nigeria (+234)</option>
<option value="683">Niue (+683)</option>
<option value="672">Norfolk Island (+672)</option>
<option value="1670">Northern Mariana Islands (+1670)</option>
<option value="47">Norway (+47)</option>
<option value="968">Oman (+968)</option>
<option value="92">Pakistan (+92)</option>
<option value="680">Palau (+680)</option>
<option value="970">Palestinian Territory, Occupied (+970)</option>
<option value="507">Panama (+507)</option>
<option value="675">Papua New Guinea (+675)</option>
<option value="595">Paraguay (+595)</option>
<option value="51">Peru (+51)</option>
<option value="63">Philippines (+63)</option>
<option value="64">Pitcairn (+64)</option>
<option value="48">Poland (+48)</option>
<option value="351">Portugal (+351)</option>
<option value="1787">Puerto Rico (+1787)</option>
<option value="974">Qatar (+974)</option>
<option value="262">Reunion (+262)</option>
<option value="40">Romania (+40)</option>
<option value="70">Russian Federation (+70)</option>
<option value="250">Rwanda (+250)</option>
<option value="590">Saint Barthelemy (+590)</option>
<option value="290">Saint Helena (+290)</option>
<option value="1869">Saint Kitts and Nevis (+1869)</option>
<option value="1758">Saint Lucia (+1758)</option>
<option value="590">Saint Martin (+590)</option>
<option value="508">Saint Pierre and Miquelon (+508)</option>
<option value="1784">Saint Vincent and the Grenadines (+1784)</option>
<option value="684">Samoa (+684)</option>
<option value="378">San Marino (+378)</option>
<option value="239">Sao Tome and Principe (+239)</option>
<option value="966">Saudi Arabia (+966)</option>
<option value="221">Senegal (+221)</option>
<option value="381">Serbia (+381)</option>
<option value="381">Serbia and Montenegro (+381)</option>
<option value="248">Seychelles (+248)</option>
<option value="232">Sierra Leone (+232)</option>
<option value="65">Singapore (+65)</option>
<option value="1">Sint Maarten (+1)</option>
<option value="421">Slovakia (+421)</option>
<option value="386">Slovenia (+386)</option>
<option value="677">Solomon Islands (+677)</option>
<option value="252">Somalia (+252)</option>
<option value="27">South Africa (+27)</option>
<option value="500">South Georgia and the South Sandwich Islands (+500)</option>
<option value="211">South Sudan (+211)</option>
<option value="34">Spain (+34)</option>
<option value="94">Sri Lanka (+94)</option>
<option value="249">Sudan (+249)</option>
<option value="597">Suriname (+597)</option>
<option value="47">Svalbard and Jan Mayen (+47)</option>
<option value="268">Swaziland (+268)</option>
<option value="46">Sweden (+46)</option>
<option value="41">Switzerland (+41)</option>
<option value="963">Syrian Arab Republic (+963)</option>
<option value="886">Taiwan, Province of China (+886)</option>
<option value="992">Tajikistan (+992)</option>
<option value="255">Tanzania, United Republic of (+255)</option>
<option value="66">Thailand (+66)</option>
<option value="670">Timor-Leste (+670)</option>
<option value="228">Togo (+228)</option>
<option value="690">Tokelau (+690)</option>
<option value="676">Tonga (+676)</option>
<option value="1868">Trinidad and Tobago (+1868)</option>
<option value="216">Tunisia (+216)</option>
<option value="90">Turkey (+90)</option>
<option value="7370">Turkmenistan (+7370)</option>
<option value="1649">Turks and Caicos Islands (+1649)</option>
<option value="688">Tuvalu (+688)</option>
<option value="256">Uganda (+256)</option>
<option value="380">Ukraine (+380)</option>
<option value="971">United Arab Emirates (+971)</option>
<option value="44">United Kingdom (+44)</option>
<option value="1">United States (+1)</option>
<option value="1">United States Minor Outlying Islands (+1)</option>
<option value="598">Uruguay (+598)</option>
<option value="998">Uzbekistan (+998)</option>
<option value="678">Vanuatu (+678)</option>
<option value="58">Venezuela (+58)</option>
<option value="84">Viet Nam (+84)</option>
<option value="1284">Virgin Islands, British (+1284)</option>
<option value="1340">Virgin Islands, U.s. (+1340)</option>
<option value="681">Wallis and Futuna (+681)</option>
<option value="212">Western Sahara (+212)</option>
<option value="967">Yemen (+967)</option>
<option value="260">Zambia (+260)</option>
<option value="263">Zimbabwe (+263)</option>                            </select>
                         </div>
                        <input type="text" name="phone" placeholder="Enter phone number">
                    </div>

                    <input type="text" placeholder="Enter your name" name="name" required>
                    <input type="email" placeholder="Enter your email" name="email" required>
                    <input type="password" placeholder="Enter your password" name="password" required>
                    <input type="text" placeholder="Property address" name="address" required>
                    <button type="submit" class="primary-btn mb-3 w-100">Continue</button>
                    </form>
                    <a href="/get/estimate"><button type="button" class="green-btn w-100 mb-3">Get Estimate Now</button></a>
                    
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="forgetPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Forget Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="/forget/password" method="post">
                        <input type="hidden" name="_token" value="th6AHaoKd7sI2AYTMqsuZt5rRiW71ORmLj9FP5mJ">                    <input type="text" placeholder="Enter your email" name="email" required>
                    <button type="submit" class="primary-btn w-100 mb-3">Get Reset Link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>        <div class="home-banner" id="home_banner">
            <div data-aos="fadeInUp" class="container d-flex flex-column flex-md-row align-items-center py-3">
                <div class="col-md-6 py-3 pe-md-4 pe-lg-5">
                    <h1 class="heading-white-2">Hosting. It’s what we do.</h1>
                    <p class="text-white-1 text-start">
                        Professionally managed flexible lettings.
                        Together, we'll earn more from your property.
                    </p>
                    <div>
                        <p class="font-semi-bold font-white fs_13 mb-2">Income estimate:</p>
                        <form method="post" action="/estimate" class="banner-form">
                            <input type="hidden" name="_token" value="th6AHaoKd7sI2AYTMqsuZt5rRiW71ORmLj9FP5mJ">                            <input type="hidden" name="type" value="estimate">
                            <input type="text" placeholder="Name" name="name" required>
                            <input type="text" placeholder="Type address" name="address" required>
                            <input type="email" placeholder="Email address"  name="email" required>
                            <input type="text" placeholder="Mobile number" name="phone" required>
                            <input type="hidden" name="bedroom" value="1" id="bedroomInput23">
                            <div class="bed-count mb-4">
                                <label class="fw-bold cursor-pointer inc243n1" id="bed_minus">-</label>
                                <label class="b-count-number"  id="bed_count">1 Bedrooms</label>
                                <label class="fw-bold cursor-pointer inc243n1" id="bed_plus">+</label>
                            </div>
                            <div class="d-flex gap-2 align-items-center mb-2">
                                <input type="checkbox" name="" id="check" class="w-auto m-0" required>
                                <label for="check" class="fs_13 font-white">By submitting this form, I accept Moka’s privacy
                                    policy</label>
                            </div>
                            <input type="submit" value="Get Estimate Now" class="green-btn fs_18">
                        </form>
                        <p class="text-bold-white-1 mt-4">Or Book a Free Consultation with our team ></p>
                    </div>
                </div>
                <div class="col-md-6 py-3">
                    <!-- <img src="new-theme23\images\Asset 1.png" alt="" srcset="" class="w-100 ps-lg-5"> -->
                    <div class="d-flex flex-column flex-sm-row flex-md-column align-items-center gap-3 gap-md-5">
                        <div class="d-flex flex-column align-items-center gap-3">
                            <img src="{{ asset('new-theme/images/iconp1.svg') }}" style="height:70px;" />
                            <span class="text-center font-white font-bold fs_18">Earn more, work less</span>
                        </div>
                        
                        <div class="d-flex flex-column align-items-center gap-3">
                            <img src="{{ asset('new-theme/images/iconp2.svg') }}" style="height:70px;" />
                            <span class="text-center font-white font-bold fs_18">Complete host management</span>
                        </div>
                        
                        <div class="d-flex flex-column align-items-center gap-3">
                            <img src="{{ asset('new-theme/images/iconp3.svg') }}" style="height:70px;" />
                            <span class="text-center font-white font-bold fs_18">You’re always in control</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
