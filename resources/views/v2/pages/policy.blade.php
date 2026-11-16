@extends('auth.newTheme.layout')
@section('seo_title', 'Privacy Notice | MOKA')
@section('seo_description', 'How MOKA collects, uses, shares and protects personal data under Malaysia’s Personal Data Protection Act, and how to contact us about it.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('new-theme23/css/blog23.css') }}?v={{ filemtime(public_path('new-theme23/css/blog23.css')) }}">
    <link rel="stylesheet" href="{{ asset('new-theme23/css/legal23.css') }}?v={{ filemtime(public_path('new-theme23/css/legal23.css')) }}">
@endpush

@section('content')
    @include('auth.newTheme.partials.header')

    <div class="blog-hero legal-hero">
        <div class="blog-hero__inner">
            <div class="blog-hero__eyebrow">Privacy</div>
            <h1>Personal Data Protection Notice</h1>
            <p class="blog-hero__meta">Applies to homemoka.com, the MOKA owner portal and stays in homes we manage. Operated by Moka Venture Sdn. Bhd. English and Bahasa Malaysia. Questions: <a href="mailto:hello@homemoka.com">hello@homemoka.com</a></p>
        </div>
    </div>

    <div class="legal-body">
        <div class="legal-layout">
            <article class="legal-article" id="legalArticle">
@if(now()->lt(\Carbon\Carbon::parse('2026-12-17')))
<div class="blog-callout"><p><strong>Updated on 16 November 2026.</strong> This notice was rewritten to describe more clearly the data we collect, where it is processed, your rights, and the cookies in use. A Bahasa Malaysia version has been added below.</p></div>
@endif
<p><strong>Last updated: 16 November 2026</strong> · <a href="#notis-bm">Baca dalam Bahasa Malaysia</a></p>
<p>This Personal Data Protection Notice (&ldquo;<strong>Notice</strong>&rdquo;) is issued by Moka Venture Sdn. Bhd. (Registration No. 202001018827 (1375147-V)) (&ldquo;<strong>MOKA</strong>&rdquo;, &ldquo;<strong>we</strong>&rdquo;, &ldquo;<strong>us</strong>&rdquo;) under the Personal Data Protection Act 2010 of Malaysia (&ldquo;<strong>PDPA</strong>&rdquo;). We are the data controller of the personal data described here.</p>
<p>It explains how we collect, use, disclose and protect your personal data when you visit homemoka.com (the &ldquo;<strong>Website</strong>&rdquo;), use the MOKA owner portal, send us a form, apply for a job with us, or stay in a home that we manage.</p>

<h2>Personal data we collect</h2>
<p>What we collect depends on how you deal with us.</p>
<ul>
<li><strong>Property owners and people asking about our services:</strong> name, contact details, the address and details of your property, identity or company details, bank account details for payments to you, your owner portal login details, and your messages to us.</li>
<li><strong>Guests:</strong> name, contact details, nationality, identity card or passport details where the law or the building&rsquo;s management requires guests to be registered, details of your booking and stay, payment and refund records (including bank account details when you ask us for a refund), your messages and your reviews.</li>
<li><strong>Job applicants:</strong> name, contact details, your CV, your work and education history, any link you give us, and anything else you choose to include in your application.</li>
<li><strong>Visitors to the Website:</strong> your device&rsquo;s IP address, browser and device type, the pages you view, the date and time of your visit and the page that referred you, together with the cookies described below.</li>
</ul>
<p><strong>Sensitive personal data.</strong> We do not ask for sensitive personal data, such as information about your health, religious beliefs, political opinions, criminal record or biometric data. We will collect it only where it is needed and only with your explicit consent. Please do not include it in a CV or a message to us unless it is necessary.</p>

<h2>Where we get your personal data</h2>
<ul>
<li>from you, when you fill in a form, register for or use the owner portal, write or speak to us, or stay with us;</li>
<li>from the booking platform, travel agent or company through which your stay was booked;</li>
<li>from the property owner who appoints us, or the person who made a booking on your behalf;</li>
<li>from the management office of a building, where guest or owner registration is required; and</li>
<li>automatically from your device when you visit the Website.</li>
</ul>

<h2>Compulsory and voluntary information</h2>
<p>Fields marked as required in our forms are compulsory. If you do not provide them, we may be unable to reply to your enquiry, prepare an estimate, consider your job application, register or host your stay, process a refund, or give you access to the owner portal. All other information is voluntary.</p>

<h2>Why we use your personal data</h2>
<ul>
<li>to reply to enquiries and prepare income estimates for property owners;</li>
<li>to manage properties for their owners, including reporting to owners and paying them;</li>
<li>to take, manage and host bookings, register guests where required, and process payments, deposits and refunds;</li>
<li>to provide and secure the owner portal;</li>
<li>to consider job applications;</li>
<li>to communicate with you about our services and your requests;</li>
<li>to understand how the Website is used and to improve it;</li>
<li>to send marketing messages, where you have agreed to receive them;</li>
<li>to prevent and detect fraud, misuse and security incidents;</li>
<li>to keep accounts and records, and to comply with tax, tourism, immigration and other laws; and</li>
<li>to establish, exercise or defend legal claims.</li>
</ul>

<h2>Consent</h2>
<p>By giving us your personal data, ticking a consent box, or continuing to use our services after being given this Notice, you consent to our processing your personal data as described here. Where the PDPA allows processing without consent, for example to perform a contract with you or to comply with a legal obligation, we rely on that.</p>
<p>You may withdraw your consent at any time by writing to us at the address below. If you do, we may not be able to continue providing the service that depends on that data. Withdrawal does not affect processing that the law permits or requires.</p>
<p>If you give us personal data about another person, such as a fellow guest, you confirm that the person has been told about this Notice and has agreed to it.</p>

<h2>Marketing</h2>
<p>We send marketing messages only where you have agreed. Every marketing email includes a way to unsubscribe, and you may also ask us at any time, in writing, to stop using your personal data for direct marketing. We do not sell your personal data.</p>

<h2>Who we share your personal data with</h2>
<p>We disclose personal data only as needed for the purposes above, to these classes of third parties:</p>
<ul>
<li>the owner of the property concerned, limited to the booking information needed for owner reporting;</li>
<li>booking platforms, payment providers and banks involved in your booking, payment or refund;</li>
<li>service providers who work for us, such as hosting, property management and channel management software, email, website analytics, website security, live chat, cleaning and maintenance contractors;</li>
<li>the management and security office of a building, where registration is required for access;</li>
<li>our professional advisers, auditors and insurers;</li>
<li>our related companies;</li>
<li>government, tax, tourism, immigration and law-enforcement authorities, courts and tribunals, where the law requires or permits; and</li>
<li>a buyer or successor, if all or part of our business is sold or reorganised.</li>
</ul>
<p>We require those who process personal data for us to protect it and to use it only on our instructions.</p>

<h2>Transfers outside Malaysia</h2>
<p>The Website and the owner portal are hosted on Google Cloud servers located in Hong Kong. Some of our service providers, including Google, Cloudflare, respond.io and the booking platforms, may process personal data in other countries where they operate, including the United States.</p>
<p>We transfer personal data outside Malaysia only as section 129 of the PDPA permits, for example where you have consented, where the transfer is needed to perform a contract with you, or where we have taken reasonable steps to make sure the data is protected to a standard comparable to the PDPA. By giving us your personal data, you consent to these transfers.</p>

<h2>Security and data breaches</h2>
<p>We take practical steps to protect personal data from loss, misuse, modification, and unauthorised or accidental access or disclosure, in line with the security standards under the PDPA. These include encrypted connections, restricted staff access, and private storage for uploaded documents. No method of transmission or storage is completely secure, and you are responsible for keeping your owner portal password confidential.</p>
<p>If a personal data breach occurs, we will notify the Personal Data Protection Commissioner and the affected individuals where the PDPA requires it.</p>

<h2>How long we keep personal data</h2>
<p>We keep personal data only for as long as it is needed for the purposes above or as the law requires. Accounting and tax records are kept for at least seven years, as Malaysian law requires. If a job application is unsuccessful, we keep it only for as long as needed to consider you for other suitable roles, and you may ask us to delete it at any time. When personal data is no longer needed, we destroy it or make it anonymous.</p>

<h2>Your rights</h2>
<p>Subject to the PDPA, you have the right to:</p>
<ul>
<li>ask for access to, and a copy of, the personal data we hold about you;</li>
<li>ask us to correct personal data that is inaccurate, incomplete, misleading or out of date;</li>
<li>withdraw your consent, or limit how we process your personal data;</li>
<li>ask us to stop using your personal data for direct marketing;</li>
<li>ask us to stop processing that is causing, or is likely to cause, you substantial damage or distress;</li>
<li>ask us to transmit your personal data to another data controller, where this is technically feasible; and</li>
<li>complain to us and, if you are not satisfied, to the Personal Data Protection Commissioner of Malaysia.</li>
</ul>
<p>To exercise a right, write to us using the details under &ldquo;Contact us&rdquo;. We may need to verify your identity. We will respond within 21 days of receiving your request, as the PDPA requires, and we may charge the fee that the law prescribes. If the PDPA allows us to refuse a request, we will tell you the reason.</p>

<h2>Cookies and similar technologies</h2>
<p>Cookies are small files stored on your device. As at 16 November 2026, the Website uses the following:</p>
<ul>
<li><strong>Strictly necessary cookies.</strong> A session cookie that keeps you signed in and remembers your place between pages, and a security token that protects the forms you submit. Both expire two hours after your visit.</li>
<li><strong>Security.</strong> Cloudflare, which protects the Website from attacks, may set a cookie to tell real visitors from automated traffic.</li>
<li><strong>Analytics.</strong> Since 13 October 2026 we use Google Analytics to count visits and see which pages are read. It sets cookies that can last up to two years and collects your IP address and device information. We also use Cloudflare Web Analytics, which does not use cookies.</li>
<li><strong>Chat.</strong> The chat button is provided by respond.io, which sets its own cookies to keep a conversation open. Its use of data is governed by respond.io&rsquo;s privacy policy.</li>
</ul>
<p>We do not use advertising or targeting cookies. If that changes, we will update this Notice first and ask for your consent where the law requires it.</p>
<p>You can block or delete cookies in your browser settings. If you block the strictly necessary cookies, signing in and submitting forms will not work. To opt out of Google Analytics on all websites, Google offers a browser add-on at <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener">tools.google.com/dlpage/gaoptout</a>.</p>

<h2>Children</h2>
<p>Our services are meant for adults. We do not knowingly collect personal data from anyone under 18 except from, or with the consent of, a parent or guardian, for example where a child is listed as a guest in a booking. If you believe we hold a child&rsquo;s personal data without that consent, please contact us and we will delete it.</p>

<h2>Links to other websites</h2>
<p>The Website links to other websites, including staymoka.com, booking platforms and social media. Their handling of personal data is governed by their own privacy notices, not this one.</p>

<h2>Changes to this Notice</h2>
<p>We may update this Notice. The date at the top shows when it was last changed. For a material change, we will display a notice at the top of this page for 30 days.</p>

<h2>Contact us</h2>
<p>For questions, requests or complaints about your personal data, contact our data protection contact:</p>
<p>Moka Venture Sdn. Bhd.<br>11.1, 11th Floor Menara Lien Hoe, 8, Persiaran Tropicana, 47410 Petaling Jaya, Selangor, Malaysia<br>Email: hello@homemoka.com<br>Telephone: +603 6789 2288</p>

<h2>Language</h2>
<p>This Notice is given in English and Bahasa Malaysia. If the two versions differ, the English version prevails.</p>

<h2 id="notis-bm" lang="ms">Notis Perlindungan Data Peribadi (Bahasa Malaysia)</h2>
<div lang="ms">
<p><strong>Kemas kini terakhir: 16 November 2026</strong></p>
<p>Notis Perlindungan Data Peribadi ini (&ldquo;<strong>Notis</strong>&rdquo;) dikeluarkan oleh Moka Venture Sdn. Bhd. (No. Pendaftaran 202001018827 (1375147-V)) (&ldquo;<strong>MOKA</strong>&rdquo;, &ldquo;<strong>kami</strong>&rdquo;) di bawah Akta Perlindungan Data Peribadi 2010 (&ldquo;<strong>APDP</strong>&rdquo;). Kami ialah pengawal data bagi data peribadi yang diterangkan di sini.</p>
<p>Notis ini menerangkan cara kami mengumpul, menggunakan, mendedahkan dan melindungi data peribadi anda apabila anda melawat homemoka.com (&ldquo;<strong>Laman Web</strong>&rdquo;), menggunakan portal pemilik MOKA, menghantar borang kepada kami, memohon pekerjaan dengan kami, atau menginap di kediaman yang kami uruskan.</p>

<h3>Data peribadi yang kami kumpul</h3>
<ul>
<li><strong>Pemilik hartanah dan orang yang bertanya tentang perkhidmatan kami:</strong> nama, butiran hubungan, alamat dan butiran hartanah anda, butiran pengenalan diri atau syarikat, butiran akaun bank untuk bayaran kepada anda, butiran log masuk portal pemilik, dan mesej anda kepada kami.</li>
<li><strong>Tetamu:</strong> nama, butiran hubungan, kewarganegaraan, butiran kad pengenalan atau pasport jika undang-undang atau pengurusan bangunan menghendaki tetamu didaftarkan, butiran tempahan dan penginapan anda, rekod bayaran dan bayaran balik (termasuk butiran akaun bank apabila anda memohon bayaran balik), mesej dan ulasan anda.</li>
<li><strong>Pemohon pekerjaan:</strong> nama, butiran hubungan, resume (CV), sejarah pekerjaan dan pendidikan, pautan yang anda berikan, dan apa-apa maklumat lain yang anda sertakan dalam permohonan anda.</li>
<li><strong>Pelawat Laman Web:</strong> alamat IP peranti anda, jenis pelayar dan peranti, halaman yang dilihat, tarikh dan masa lawatan serta halaman yang merujuk anda, bersama kuki yang diterangkan di bawah.</li>
</ul>
<p><strong>Data peribadi sensitif.</strong> Kami tidak meminta data peribadi sensitif, seperti maklumat tentang kesihatan, kepercayaan agama, pendapat politik, rekod jenayah atau data biometrik anda. Kami hanya akan mengumpulnya jika diperlukan dan hanya dengan persetujuan nyata anda. Sila jangan sertakan data sedemikian dalam resume atau mesej kepada kami melainkan perlu.</p>

<h3>Sumber data peribadi anda</h3>
<ul>
<li>daripada anda, apabila anda mengisi borang, mendaftar atau menggunakan portal pemilik, menulis atau bercakap dengan kami, atau menginap dengan kami;</li>
<li>daripada platform tempahan, ejen pelancongan atau syarikat yang membuat tempahan penginapan anda;</li>
<li>daripada pemilik hartanah yang melantik kami, atau orang yang membuat tempahan bagi pihak anda;</li>
<li>daripada pejabat pengurusan bangunan, jika pendaftaran tetamu atau pemilik dikehendaki; dan</li>
<li>secara automatik daripada peranti anda apabila anda melawat Laman Web.</li>
</ul>

<h3>Maklumat wajib dan sukarela</h3>
<p>Ruangan yang ditanda sebagai wajib dalam borang kami adalah wajib diisi. Jika anda tidak memberikannya, kami mungkin tidak dapat menjawab pertanyaan anda, menyediakan anggaran, menimbang permohonan pekerjaan anda, mendaftarkan atau menerima penginapan anda, memproses bayaran balik, atau memberi anda akses kepada portal pemilik. Semua maklumat lain adalah sukarela.</p>

<h3>Tujuan kami menggunakan data peribadi anda</h3>
<ul>
<li>untuk menjawab pertanyaan dan menyediakan anggaran pendapatan bagi pemilik hartanah;</li>
<li>untuk mengurus hartanah bagi pihak pemiliknya, termasuk melapor kepada pemilik dan membuat bayaran kepada mereka;</li>
<li>untuk menerima, mengurus dan melaksanakan tempahan, mendaftarkan tetamu jika dikehendaki, serta memproses bayaran, deposit dan bayaran balik;</li>
<li>untuk menyediakan dan melindungi portal pemilik;</li>
<li>untuk menimbang permohonan pekerjaan;</li>
<li>untuk berhubung dengan anda tentang perkhidmatan kami dan permintaan anda;</li>
<li>untuk memahami cara Laman Web digunakan dan menambah baiknya;</li>
<li>untuk menghantar mesej pemasaran, jika anda telah bersetuju menerimanya;</li>
<li>untuk mencegah dan mengesan penipuan, penyalahgunaan dan insiden keselamatan;</li>
<li>untuk menyimpan akaun dan rekod, serta mematuhi undang-undang cukai, pelancongan, imigresen dan undang-undang lain; dan</li>
<li>untuk membuat, melaksanakan atau membela tuntutan undang-undang.</li>
</ul>

<h3>Persetujuan</h3>
<p>Dengan memberikan data peribadi anda kepada kami, menanda kotak persetujuan, atau terus menggunakan perkhidmatan kami selepas diberikan Notis ini, anda bersetuju data peribadi anda diproses seperti yang diterangkan di sini. Jika APDP membenarkan pemprosesan tanpa persetujuan, contohnya untuk melaksanakan kontrak dengan anda atau mematuhi kewajipan undang-undang, kami bergantung pada kebenaran itu.</p>
<p>Anda boleh menarik balik persetujuan anda pada bila-bila masa dengan menulis kepada kami di alamat di bawah. Jika anda berbuat demikian, kami mungkin tidak dapat terus menyediakan perkhidmatan yang bergantung pada data tersebut. Penarikan balik tidak menjejaskan pemprosesan yang dibenarkan atau dikehendaki oleh undang-undang.</p>
<p>Jika anda memberikan data peribadi orang lain kepada kami, seperti tetamu yang menginap bersama anda, anda mengesahkan bahawa orang itu telah dimaklumkan tentang Notis ini dan bersetuju dengannya.</p>

<h3>Pemasaran</h3>
<p>Kami menghantar mesej pemasaran hanya jika anda telah bersetuju. Setiap e-mel pemasaran mengandungi cara untuk berhenti melanggan, dan anda juga boleh meminta kami secara bertulis pada bila-bila masa supaya berhenti menggunakan data peribadi anda untuk pemasaran langsung. Kami tidak menjual data peribadi anda.</p>

<h3>Pihak yang menerima data peribadi anda</h3>
<p>Kami mendedahkan data peribadi hanya setakat yang perlu bagi tujuan di atas, kepada golongan pihak ketiga berikut:</p>
<ul>
<li>pemilik hartanah berkenaan, terhad kepada maklumat tempahan yang diperlukan untuk laporan pemilik;</li>
<li>platform tempahan, penyedia pembayaran dan bank yang terlibat dalam tempahan, bayaran atau bayaran balik anda;</li>
<li>penyedia perkhidmatan yang bekerja untuk kami, seperti pengehosan, perisian pengurusan hartanah dan saluran tempahan, e-mel, analitik laman web, keselamatan laman web, sembang langsung, serta kontraktor pembersihan dan penyelenggaraan;</li>
<li>pejabat pengurusan dan keselamatan bangunan, jika pendaftaran dikehendaki untuk akses;</li>
<li>penasihat profesional, juruaudit dan penanggung insurans kami;</li>
<li>syarikat berkaitan kami;</li>
<li>pihak berkuasa kerajaan, cukai, pelancongan, imigresen dan penguatkuasaan undang-undang, mahkamah dan tribunal, jika dikehendaki atau dibenarkan oleh undang-undang; dan</li>
<li>pembeli atau pengganti, jika semua atau sebahagian perniagaan kami dijual atau disusun semula.</li>
</ul>
<p>Kami menghendaki pihak yang memproses data peribadi bagi pihak kami supaya melindunginya dan menggunakannya hanya mengikut arahan kami.</p>

<h3>Pemindahan ke luar Malaysia</h3>
<p>Laman Web dan portal pemilik dihoskan pada pelayan Google Cloud yang terletak di Hong Kong. Sesetengah penyedia perkhidmatan kami, termasuk Google, Cloudflare, respond.io dan platform tempahan, mungkin memproses data peribadi di negara lain tempat mereka beroperasi, termasuk Amerika Syarikat.</p>
<p>Kami memindahkan data peribadi ke luar Malaysia hanya sebagaimana yang dibenarkan oleh seksyen 129 APDP, contohnya jika anda telah bersetuju, jika pemindahan itu perlu untuk melaksanakan kontrak dengan anda, atau jika kami telah mengambil langkah yang munasabah untuk memastikan data itu dilindungi pada tahap yang setara dengan APDP. Dengan memberikan data peribadi anda kepada kami, anda bersetuju dengan pemindahan ini.</p>

<h3>Keselamatan dan pelanggaran data</h3>
<p>Kami mengambil langkah praktikal untuk melindungi data peribadi daripada kehilangan, penyalahgunaan, pengubahsuaian, dan akses atau pendedahan tanpa kebenaran atau secara tidak sengaja, selaras dengan standard keselamatan di bawah APDP. Ini termasuk sambungan yang disulitkan, akses kakitangan yang terhad, dan storan peribadi bagi dokumen yang dimuat naik. Tiada kaedah penghantaran atau penyimpanan yang selamat sepenuhnya, dan anda bertanggungjawab merahsiakan kata laluan portal pemilik anda.</p>
<p>Jika berlaku pelanggaran data peribadi, kami akan memaklumkan Pesuruhjaya Perlindungan Data Peribadi dan individu yang terjejas jika dikehendaki oleh APDP.</p>

<h3>Tempoh penyimpanan data peribadi</h3>
<p>Kami menyimpan data peribadi hanya selama yang diperlukan bagi tujuan di atas atau sebagaimana yang dikehendaki oleh undang-undang. Rekod perakaunan dan cukai disimpan sekurang-kurangnya tujuh tahun, seperti yang dikehendaki oleh undang-undang Malaysia. Jika permohonan pekerjaan tidak berjaya, kami menyimpannya hanya selama yang perlu untuk menimbang anda bagi jawatan lain yang sesuai, dan anda boleh meminta kami memadamkannya pada bila-bila masa. Apabila data peribadi tidak lagi diperlukan, kami memusnahkannya atau menjadikannya tanpa nama.</p>

<h3>Hak anda</h3>
<p>Tertakluk kepada APDP, anda berhak untuk:</p>
<ul>
<li>meminta akses kepada, dan salinan, data peribadi anda yang kami simpan;</li>
<li>meminta kami membetulkan data peribadi yang tidak tepat, tidak lengkap, mengelirukan atau tidak terkini;</li>
<li>menarik balik persetujuan anda, atau mengehadkan cara kami memproses data peribadi anda;</li>
<li>meminta kami berhenti menggunakan data peribadi anda untuk pemasaran langsung;</li>
<li>meminta kami menghentikan pemprosesan yang menyebabkan, atau mungkin menyebabkan, kerosakan atau kesusahan yang substansial kepada anda;</li>
<li>meminta kami menghantar data peribadi anda kepada pengawal data lain, jika ia boleh dilaksanakan dari segi teknikal; dan</li>
<li>membuat aduan kepada kami dan, jika anda tidak berpuas hati, kepada Pesuruhjaya Perlindungan Data Peribadi Malaysia.</li>
</ul>
<p>Untuk menggunakan hak anda, tulis kepada kami menggunakan butiran di bawah &ldquo;Hubungi kami&rdquo;. Kami mungkin perlu mengesahkan identiti anda. Kami akan menjawab dalam masa 21 hari dari tarikh menerima permintaan anda, seperti yang dikehendaki oleh APDP, dan kami boleh mengenakan fi yang ditetapkan oleh undang-undang. Jika APDP membenarkan kami menolak sesuatu permintaan, kami akan memaklumkan sebabnya kepada anda.</p>

<h3>Kuki dan teknologi yang serupa</h3>
<p>Kuki ialah fail kecil yang disimpan pada peranti anda. Setakat 16 November 2026, Laman Web menggunakan yang berikut:</p>
<ul>
<li><strong>Kuki yang amat perlu.</strong> Kuki sesi yang mengekalkan log masuk anda dan mengingati kedudukan anda antara halaman, dan token keselamatan yang melindungi borang yang anda hantar. Kedua-duanya tamat tempoh dua jam selepas lawatan anda.</li>
<li><strong>Keselamatan.</strong> Cloudflare, yang melindungi Laman Web daripada serangan, mungkin menetapkan kuki untuk membezakan pelawat sebenar daripada trafik automatik.</li>
<li><strong>Analitik.</strong> Sejak 13 Oktober 2026 kami menggunakan Google Analytics untuk mengira lawatan dan melihat halaman yang dibaca. Ia menetapkan kuki yang boleh kekal sehingga dua tahun dan mengumpul alamat IP serta maklumat peranti anda. Kami juga menggunakan Cloudflare Web Analytics, yang tidak menggunakan kuki.</li>
<li><strong>Sembang.</strong> Butang sembang disediakan oleh respond.io, yang menetapkan kukinya sendiri untuk mengekalkan perbualan. Penggunaan datanya tertakluk kepada dasar privasi respond.io.</li>
</ul>
<p>Kami tidak menggunakan kuki pengiklanan atau penyasaran. Jika ini berubah, kami akan mengemas kini Notis ini terlebih dahulu dan meminta persetujuan anda jika dikehendaki oleh undang-undang.</p>
<p>Anda boleh menyekat atau memadam kuki melalui tetapan pelayar anda. Jika anda menyekat kuki yang amat perlu, log masuk dan penghantaran borang tidak akan berfungsi. Untuk menarik diri daripada Google Analytics di semua laman web, Google menyediakan alat tambah pelayar di <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener">tools.google.com/dlpage/gaoptout</a>.</p>

<h3>Kanak-kanak</h3>
<p>Perkhidmatan kami adalah untuk orang dewasa. Kami tidak dengan sengaja mengumpul data peribadi daripada sesiapa yang berumur di bawah 18 tahun kecuali daripada, atau dengan persetujuan, ibu bapa atau penjaga, contohnya apabila kanak-kanak disenaraikan sebagai tetamu dalam tempahan. Jika anda percaya kami menyimpan data peribadi kanak-kanak tanpa persetujuan itu, sila hubungi kami dan kami akan memadamkannya.</p>

<h3>Pautan ke laman web lain</h3>
<p>Laman Web mempunyai pautan ke laman web lain, termasuk staymoka.com, platform tempahan dan media sosial. Pengendalian data peribadi oleh mereka tertakluk kepada notis privasi mereka sendiri, bukan Notis ini.</p>

<h3>Perubahan kepada Notis ini</h3>
<p>Kami boleh mengemas kini Notis ini. Tarikh di bahagian atas menunjukkan bila ia terakhir diubah. Bagi perubahan yang material, kami akan memaparkan makluman di bahagian atas halaman ini selama 30 hari.</p>

<h3>Hubungi kami</h3>
<p>Untuk pertanyaan, permintaan atau aduan tentang data peribadi anda, hubungi pegawai yang bertanggungjawab bagi perlindungan data kami:</p>
<p>Moka Venture Sdn. Bhd.<br>11.1, Tingkat 11, Menara Lien Hoe, 8, Persiaran Tropicana, 47410 Petaling Jaya, Selangor, Malaysia<br>E-mel: hello@homemoka.com<br>Telefon: +603 6789 2288</p>

<h3>Bahasa</h3>
<p>Notis ini diberikan dalam bahasa Inggeris dan Bahasa Malaysia. Jika terdapat percanggahan antara kedua-dua versi, versi bahasa Inggeris akan diguna pakai.</p>
</div>
            </article>
            <aside class="legal-aside">
                <div class="legal-toc" id="legalToc" hidden>
                    <div class="legal-toc__title">On this page</div>
                    <ol></ol>
                </div>
                <div class="legal-card">
                    <b>Need to talk to a person?</b>
                    <p>Email <a href="mailto:hello@homemoka.com">hello@homemoka.com</a> or call <a href="tel:60367892288">+603 6789 2288</a>, Monday to Friday, 9am to 6pm.</p>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var hs = document.querySelectorAll('#legalArticle h2'), toc = document.getElementById('legalToc'), ol = toc.querySelector('ol');
    if (hs.length < 3) return;
    hs.forEach(function (h, i) { h.id = h.id || 'section-' + (i + 1); var li = document.createElement('li'), a = document.createElement('a'); a.href = '#' + h.id; a.textContent = h.textContent; li.appendChild(a); ol.appendChild(li); });
    toc.hidden = false;
})();
</script>
@endpush
