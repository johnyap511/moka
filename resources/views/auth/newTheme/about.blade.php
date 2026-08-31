@extends('auth.newTheme.layout')

@section('content')
    <div class="about-banner-outer">
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
                        @csrf                        <input type="email" name="email" placeholder="Email or phone number" required>
                        <input type="password" name="password" placeholder="Enter your password" required>
                        <a href="/about#">
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
                    <p class="mt-4 mb-3">Don't have account <a href="/about#" class="text-decoration-underline" data-bs-toggle="modal" data-bs-target="#signupModal"> Sign Up </a></p>
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
                        @csrf                        <div class="phone-input-sec">
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
                    <p class="my-4">Already have an account <a href="/about#" class="text-decoration-underline" data-bs-toggle="modal" data-bs-target="#loninModal"> Log in </a>
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
                        @csrf                    <div class="phone-input-sec">
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
                        @csrf                    <input type="text" placeholder="Enter your email" name="email" required>
                    <button type="submit" class="primary-btn w-100 mb-3">Get Reset Link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>        <div data-aos="fadeInUp" class="about-banner" id="about_banner">
            <div class="container d-flex flex-column align-items-center justify-content-center py-3">
                <h1 class="heading-white-1 text-center mb-4">We are MOKA</h1>
                <div class="col-md-7">
                    <p class="text-white-1 text-center m-0">
                        We're helping homeowners make more of their property.
                        Unlock your potential by opening your doors to guests -
                        host more, earn more, do more.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div data-aos="fadeInUp" class="container section-height my-5 py-5">
        <div class="d-flex flex-column flex-md-row align-items-center">
            <div class="col-md-6 py-3 pe-md-4 pe-lg-5">
                <img src="{{ asset('new-theme23/images/Asset%2017.png') }}" alt="" srcset="" data-aos="fade-right" class="w-100 pe-lg-5">
            </div>
            <div class="col-md-6 py-3 ps-md-4 ps-lg-5 make-order-first">
                <h2 class="heading-orange-2 mb-4">
                    Hosting opens a world
                    of opportunity.
                </h2>
                <p class="text-green-1 mb-4">
                    People have never been more free, and the world never more
                    open. We choose our own paths and make our own chances.
                </p>
                <p class="text-green-1">
                    In this era of opportunity, your home can be more than just
                    bricks, mortar, and a mortgage. Host, and your home can earn
                    that round-the-world trip, the time to pursue your passion, or
                    the chance to start your own business.
                </p>
            </div>
        </div>
    </div>
    <div class="bg-white section-height">
        <div data-aos="fadeInUp" class="container my-5 py-5">
            <div class="d-flex flex-column flex-md-row align-items-center">
                <div class="col-md-6 py-3 pe-md-4 pe-lg-5">
                    <h2 class="heading-orange-2 mb-4">
                        But it isn't always easy.
                    </h2>
                    <p class="text-green-1 mb-4">
                    Sourcing, managing and dealing with a constant
                    changeover of tenants can be tricky.
                    </p>
                    <p class="text-green-1">
                    Responding to messages at 1am. Finding a good -
                    and reliable - cleaner. Leaving your dinner to deliver
                    spare keys. Without a host partner, you’ll find that
                    hosting guests from around the world doesn't free
                    you, it ties you down.
                    </p>
                </div>
                <div class="col-md-6 py-3 ps-md-4 ps-lg-5">
                    <img src="{{ asset('new-theme23/images/Asset%2018.png') }}" alt="" srcset="" data-aos="fade-left" class="w-100 ps-lg-5">
                </div>
            </div>
        </div>
    </div>
    <div data-aos="fadeInUp" class="container section-height my-5 py-5">
        <div class="d-flex flex-column flex-md-row align-items-center">
            <div class="col-md-6 py-3 pe-md-4 pe-lg-5">
                <img src="{{ asset('new-theme23/images/Asset%2019.png') }}" alt="" srcset="" data-aos="fade-right" class="w-100 pe-lg-5">
            </div>
            <div class="col-md-6 py-3 ps-md-4 ps-lg-5 make-order-first">
                <h2 class="heading-orange-2 mb-4">
                    That's why we created a
                    professional hosting service.
                </h2>
                <p class="text-green-1 mb-4">
                    We'll do the cleaning, the guest communications and
                    run the listings. We'll support maintenance, advise on
                    pricing and help maximise occupancy, too.
                </p>
                <p class="text-green-1">
                    We’ve managed 250,000 bookings across five
                    continents, and have been defining great, modern
                    hosting since 2018.
                </p>
            </div>
        </div>
    </div>
    <div class="bg-white section-height">
        <div data-aos="fadeInUp" class="container my-5 py-5">
            <div class="d-flex flex-column flex-md-row align-items-center">
                <div class="col-md-6 py-3 pe-md-4 pe-lg-5">
                    <h2 class="heading-orange-2 mb-4">
                        Owners are our partners,
                        and we succeed as a team.

                    </h2>
                    <p class="text-green-1 mb-4">
                        That's why we created a professional hosting service.
                        We'll do the cleaning, the guest communications and run
                        the listings. We'll support maintenance, advise on pricing
                        and help maximise occupancy, too.
                    </p>
                    <p class="text-green-1">
                        We’ve managed 250,000 bookings across five continents,
                        and have been defining great, modern hosting since 2018.
                    </p>
                </div>
                <div class="col-md-6 py-3 ps-md-4 ps-lg-5">
                    <img src="{{ asset('new-theme23/images/Asset%2020.png') }}" alt="" srcset="" data-aos="fade-left" class="w-100 ps-lg-5">
                </div>
            </div>
        </div>
    </div>
    <div class="container d-flex flex-column flex-lg-row my-4 py-3">
        <div class="flex-grow-1 p-3">
            <h2 class="heading-green-3 text-center m-0">100,000+</h2>
            <p class="text-green-2 text-center m-0">Trips hosted</p>
        </div>
        <div class="flex-grow-1 p-3">
            <h2 class="heading-green-3 text-center m-0">70%</h2>
            <p class="text-green-2 text-center m-0">Occupancy rate</p>
        </div>
        <div class="flex-grow-1 p-3">
            <h2 class="heading-green-3 text-center m-0">RM10 Million+</h2>
            <p class="text-green-2 text-center m-0">Revenue earned for hosts</p>
        </div>
        <div class="flex-grow-1 p-3">
            <p class="text-green-2 text-center m-0">Awarded</p>
            <h2 class="heading-green-3 text-center m-0">Superhost & Preferred Host</h2>
        </div>
        <div class="flex-grow-1 p-3">
            <h2 class="heading-green-3 text-center m-0">4.9/5</h2>
            <p class="text-green-2 text-center m-0">Overall Rating Review</p>
        </div>
    </div>
    <div class="container">
        <h1 class="heading-orange-1 text-center">
            Our Portfolio
        </h1>
        <p class="fs_25 text-center">
            We currently have a total of 12 portfolios and we are striving for more
        </p>
        <div class="my-5 py-4 px-5 position-relative">
            <div class="owl-carousel owl-theme" id="portfolio_carousel">
                <div class="portfolio-card">
                    <img src="{{ asset('new-theme23/images/Arte%20Cheras%20(Night%20view).jpg') }}" alt="" srcset="">
                    <p class="text-green-2 p-4">
                        EkoCheras Service Apartment,
                        Jalan Cheras
                    </p>
                </div>
                <div class="portfolio-card">
                    <img src="{{ asset('new-theme23/images/Asset%2023.png') }}" alt="" srcset="">
                    <p class="text-green-2 p-4">
                        Bell Suites, Sunsuria City,
                        Sepang
                    </p>
                </div>
                <div class="portfolio-card">
                    <img src="{{ asset('new-theme23/images/Asset%2024.png') }}" alt="" srcset="">
                    <p class="text-green-2 p-4">
                        Damai 88 Serviced Residence,
                        Jalan Ampang
                    </p>
                </div>
                <div class="portfolio-card">
                    <img src="{{ asset('new-theme23/images/Asset%2025.png') }}" alt="" srcset="">
                    <p class="text-green-2 p-4">
                        Concerto North Kiara,
                        Dutamas
                    </p>
                </div>
                <div class="portfolio-card">
                    <img src="{{ asset('new-theme23/images/KL%20Gateway%20Premium%20Residence.jpg') }}" alt="" srcset="">
                    <p class="text-green-2 p-4">
                        KL Gateway Premium Residence
                    </p>
                </div>
                <div class="portfolio-card">
                    <img src="{{ asset('new-theme23/images/Queensville.jpg') }}" alt="" srcset="">
                    <p class="text-green-2 p-4">
                        Queensville
                    </p>
                </div>
                <div class="portfolio-card">
                    <img src="{{ asset('new-theme23/images/Trion%20KL.jpg') }}" alt="" srcset="">
                    <p class="text-green-2 p-4">
                        Trion KL
                    </p>
                </div>
                <div class="portfolio-card">
                    <img src="{{ asset('new-theme23/images/UNA.jpg') }}" alt="" srcset="">
                    <p class="text-green-2 p-4">
                        UNA
                    </p>
                </div>
            </div>
            <div class="portfolio-carousel-nav" id="portfolio_carousel_nav"></div>
        </div>
    </div>
    <div class="bg-white section-height">
        <div class="container my-5 py-5">
            <h1 class="heading-orange-1 text-center">
                Here's what our clients are saying about us
            </h1>
            <p class="fs_18 font-semi-bold text-center my-4">
                Rated 4.8 out of 5 from <strong class="link-yellow font-semi-bold">Guest Reviews</strong>
            </p>
            <div class="position-relative">
                <div class="owl-carousel owl-theme" id="client_review_carousel">
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Really love the duplex apartment. !
                                <br>
                                10
                                <br>
                                The whole apartment was very clean and comfortable. Spacious living area, dining area and open
                                bedroom. The self check-in instruction was given by the staff and they replied promptly to my
                                question. Really worth the price and highly recommended!
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Klang</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Nice place for short trip in KL
                                <br>
                                10
                                <br>
                                1. Staff is courtesy and nice. With proper check in instruction and prompt response.
                                2. Convenient place for shopping and each to get foed at surrounding area. 3. Suitable for couple as the
                                apartment design is quite unique.
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Peggy</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Exceptional
                                <br>
                                10
                               <br>
                                There is a washing machine, microwave, fridge.. there are 2 aircones 2 fans. Lepastu please have drinks for
                                the guest. It's perfect. The withdrawal from flight can rest comfortably here... calm down... the loose end
                                can come here... best... worth Baloii
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Istikharah</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Wonderful
                                <br>
                                10
                                <br>
                                Lovely apartment, good location for brief stop close to airport lots of local eating places and convenience
                                stores nearby
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Deborah</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Very worth the money!
                                <br>
                                10
                               <br>
                                The view was amaaaaaazing and the simple yet pretty design was also good, no unnecessary decorations. staff
                                was helpful, and the facilities were good
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Syazlia</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Nice apartment!
                                <br>
                                10
                                <br>
                                Nice apartment
                                The place was clean and spacious. Communication went well. The building has a great pool where you can
                                really swim. We took the apartment for the proximity to the airport. There is a Thai restaurant at the
                                ground floor and a supermarket very close.
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Horea</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Great stay would recommend !
                                <br>
                                10
                                <br>
                                Comfortable, minimalistic and clean space. Clear guidelines on check in + check out %
                                procedures and helpful staff. Good location, lots of food choices available at the i
                                mall below
                                .
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Elkan</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Great stay!
                                <br/>
                                © • They had a lovely swing in the middle of the living room.
                                The pool was beautiful
                                The lobby's residence is connected to a mall - this is extremely convenient for your shopping and necessities.
                                There was a filter on the kitchen tab - | stayed at two other residences that did not provide drinkable water.
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Elli</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                excellent apartments in KL
                                <br/>
                                © the apartments are big and very comfortable with anything you might need. The convenience of having a shopping mall downstairs and close connection to MRT.
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Arcos Taiwan</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                ransit to Another place
                                <br/>
                                © Plenty of eateries around
                                There's a washing machine in the unit
                                Calling a Grab car is easy
                                The place is quiet
                                The room is very spacious, with two bathrooms. Basic kitchen utensils provided.
                                Good WiFi internet connection.
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Geminian</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Excellent experience.
                                <br/>
                                10
                                <br/>
                                © Great location, easy to get in and out of the city. The staff was wonderful. The hotel is well maintained. The beds were very firm, too firm for my liking. The food was good. I, especially, enjoyed the view of the sunset.
                                The rooms were clean, very comfortable, and the staff was amazing. They went over and beyond to help make our stay enjoyable. I highly recommend this hotel for anyone visiting KL.
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Rajes</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Exceptional
                                <br/>
                                10
                                <br/>
                                © located in the mall so you have everything at your doorstep. train station is a short walk and can easily get to more central places like bukit bintang and kI sentral. pools are on the rooftop, make sure you walk across the sky bridge if you're staying in J block. took me a couple of days to figure that out, that's also where the sauna/fitness centre is. nice view from the rooftop and the guards at the lg swipe door to access the lifts are very friendly and so were the staff. whatsapp instructions are very easy to follow and find.
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Chris</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="client-review-card">
                            <div class="mb-2">
                                <i class="fa-solid fa-quote-left"></i>
                            </div>
                            <p class="m-0 flex-grow-1">
                                Ekocheras by MOKA
                                <br/>
                                10
                                <br/>
                                © I like everything. The place is spacious and the view is amazing. It is comfortable and provide many amenities. The one love the most is it has a washing machine and a place to dry the clothes.
                            </p>
                            <div class="client-review-profile pt-3">
                                
                                <div>
                                    <h4 class="m-0 font-semi-bold fs-6">Rozita</h4>
                                    <p class="m-0 fs_14">Customer</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="client-review-carousel-nav" id="client_review_carousel_nav"></div>
                <div class="client-review-carousel-dot mt-2" id="client_review_carousel_dot"></div>
            </div>
        </div>
    </div>
    <div data-aos="fadeInUp" class="container section-height my-5 py-5">
        <div class="d-flex flex-column flex-md-row align-items-center">
            <div class="col-md-6 py-3 pe-md-4 pe-lg-5">
                <img src="{{ asset('new-theme23/images/Asset%2021.png') }}" alt="" srcset="" data-aos="fade-right" class="w-100 pe-lg-5">
            </div>
            <div class="col-md-6 py-3 ps-md-4 ps-lg-5 make-order-first">
                <h2 class="heading-orange-1 mb-4">
                    About Us
                </h2>
                <p class="text-green-1 mb-4">
                    Moka Venture (MOKA), formerly known as Mokahome was
                    established in the year of 2018 by a young ambitious
                    entrepreneur – Sam Kong. The motivation was simply to
                    provide a comprehensive property management solution
                    for property owners in hospitality business.
                </p>
                <p class="text-green-1">
                    MOKA has since assisted landlords to turn their properties
                    into income generating assets while providing excellent
                    customer service to their tenants. All the success stories
                    bring us closer to our vision of becoming one of the largest
                    hospitality group in South East Asia.
                </p>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-5 pt-4">
            <a href="https://wa.me/message/GJMYMABOT7CSG1" target="_blank">
                <button class="primary-btn">Chat with us now</button>
            </a>
        </div>
    </div>
    <div class="bg-white pb-5">
        <div class="container mt-5 py-5">
            <h1 class="heading-orange-1 text-center">
                Our Partners
            </h1>
            <img src="{{ asset('new-theme23/images/Asset%206.png') }}" alt="" class="mt-5 pt-3 w-100">
        </div>
    </div>
    <div class="bottom-banner py-5">
        <div class="container">
            <h2 class="heading-white-2 text-center">Find out how much your property could earn!</h2>
            <div class="d-flex justify-content-center mt-5">
                <a href="/">
                    <button class="white-btn">Get a quick estimate (Free)</button>
                </a>
            </div>
            <p class="text-white-1 m-0 text-center mt-5">Takes just 30 seconds.</p>
        </div>
    </div>
</div>
@endsection
