<?php
/**
 * Bản tiếng Anh (Polylang): Giao diện → Tạo site mẫu → "Tạo bản tiếng Anh".
 * Tạo 2 ngôn ngữ (Tiếng Việt mặc định, English ở /en/), 3 nhóm, 4 dịch vụ cho khách nước ngoài, trang chủ / About us /
 * Contact us, footer và menu tiếng Anh; nối bản dịch với trang tiếng Việt tương ứng. Không đụng nội dung tiếng Việt.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dữ liệu tiếng Anh.
 *
 * @return array
 */
function sgd_en_data() {
	$p_std = "Free consultation | Review your plan, business lines and timeline; fixed quote | Within 1 day\nPrepare documents | We draft every form; you sign abroad or in Vietnam | 1 – 2 days\nFile & follow up | We file online with the authorities and handle any requests | Per authority's processing time\nHandover | Certificates, seal and a checklist of next compliance steps | On issuance";
	return array(
		// Slug EN => [tên, icon, thứ tự, mô tả, slug nhóm tiếng Việt tương ứng].
		'groups'   => array(
			'company-formation' => array( 'Company formation', 'building', 1, 'Set up a 100% foreign-owned company, joint venture or representative office in Vietnam – from investment license to tax registration, handled by English-speaking consultants.', 'thanh-lap-doanh-nghiep' ),
			'accounting-tax'    => array( 'Accounting & tax', 'calculator', 2, 'Monthly bookkeeping, tax returns, payroll and annual financial statements for foreign-invested companies and representative offices – management reports in English.', 'ke-toan' ),
			'other-services'    => array( 'Trademark & other services', 'shield', 3, 'Trademark registration, digital signatures, e-invoices and other compliance services for businesses in Vietnam.', 'dich-vu-khac' ),
		),
		'services' => array(
			array(
				'slug'      => 'fdi-company-establishment',
				'vi'        => 'thanh-lap-cong-ty-von-nuoc-ngoai',
				'group'     => 'company-formation',
				'short'     => 'FDI company',
				'title'     => 'FDI company establishment in Vietnam',
				'excerpt'   => 'Company establishment for foreign investors in Vietnam: Investment Registration Certificate (IRC), Enterprise Registration Certificate (ERC), seal, capital account and post-licensing compliance.',
				'subtitle'  => 'One-stop service for foreign investors – from investment license to tax registration',
				'price'     => 'Contact us',
				'duration'  => 'IRC + ERC: per project',
				'icon'      => 'globe',
				'featured'  => true,
				'includes'  => "Advice on business lines open to foreign investors and market-access conditions\nPreparing and filing the Investment Registration Certificate (IRC) application\nPreparing and filing the Enterprise Registration Certificate (ERC) application\nCompany seal, capital account and initial tax registration\nPost-licensing compliance: capital contribution, reporting, accounting",
				'documents' => "Passport (individual investor) or certificate of incorporation (corporate investor), legalised and translated\nProof of financial capacity (bank statement or audited financial statements)\nOffice lease agreement in Vietnam\nProposed business lines, charter capital and project scale",
				'process'   => "Consulting | Review business lines and conditions for foreign investment | 1 – 2 days\nInvestment certificate | Prepare and file the IRC application | Per authority's processing time\nEnterprise certificate | File the ERC application after the IRC is issued | 3 working days\nPost-licensing | Seal, capital account, tax registration, capital contribution | After licensing",
				'faq'       => "What is the difference between IRC and ERC? | The IRC approves the investment project; the ERC establishes the company. Most foreign-invested companies need both.\nIs there a minimum capital requirement? | It depends on the business line. Capital must be sufficient for the project; some conditional business lines have specific requirements.\nDo I need to travel to Vietnam? | Not necessarily. Documents can be signed abroad and legalised; we handle the filing.",
				'content'   => array(
					'Who is this service for?' => 'Foreign individuals and companies who want to set up a 100% foreign-owned or joint-venture company in Vietnam. We check market-access conditions for your business lines before filing, so your application is accepted the first time.',
					'What you receive'         => 'Investment Registration Certificate, Enterprise Registration Certificate, company seal, tax registration and a checklist of compliance tasks after licensing (capital contribution deadline, reporting, accounting and tax filing).',
				),
			),
			array(
				'slug'      => 'representative-office-vietnam',
				'vi'        => 'thanh-lap-van-phong-dai-dien-nuoc-ngoai',
				'group'     => 'company-formation',
				'short'     => 'Representative office',
				'title'     => 'Representative office of a foreign company in Vietnam',
				'excerpt'   => 'Set up a representative office to research the market, promote your brand and connect with partners in Vietnam – license, seal, tax code and staff registration.',
				'subtitle'  => 'A low-cost first step into the Vietnamese market',
				'price'     => 'Contact us',
				'duration'  => 'Per authority\'s processing time',
				'icon'      => 'pin',
				'featured'  => true,
				'includes'  => "Advice on whether a representative office or a company suits your plan\nPreparing and filing the representative office license application\nSeal, tax code and bank account for the office\nRegistering the chief representative and local staff\nPeriodic reports and personal income tax filing",
				'documents' => "Certificate of incorporation of the foreign company (legalised, translated)\nAudited financial statements or proof of operation for the latest year\nPassport of the chief representative\nOffice lease agreement in Vietnam",
				'process'   => $p_std,
				'faq'       => "Can a representative office sign sales contracts? | No. A representative office cannot do business or earn revenue in Vietnam; it may research the market, promote and liaise on behalf of the parent company.\nHow long is the license valid? | Usually up to 5 years and renewable, subject to the parent company's own registration term.",
				'content'   => array(
					'Representative office or company?' => 'A representative office is ideal for market research and partner support with low running costs. If you plan to sell, invoice or employ a large team, a foreign-invested company is the right vehicle. We help you choose before you file.',
				),
			),
			array(
				'slug'      => 'tax-and-accounting-service',
				'vi'        => 'ke-toan-tron-goi',
				'group'     => 'accounting-tax',
				'short'     => 'Tax & accounting',
				'title'     => 'Tax and accounting service in Vietnam',
				'excerpt'   => 'Monthly bookkeeping, tax filing, payroll and annual financial statements for foreign-invested companies and representative offices in Vietnam – reports in English.',
				'subtitle'  => 'Bookkeeping, tax returns and financial statements – reports in English',
				'price'     => 'Contact us',
				'duration'  => 'Monthly / quarterly / yearly',
				'icon'      => 'calculator',
				'featured'  => true,
				'includes'  => "Monthly bookkeeping in line with Vietnamese Accounting Standards\nVAT, corporate income tax and personal income tax returns\nPayroll and social insurance declarations\nAnnual financial statements and tax finalisation\nManagement reports in English",
				'documents' => "Sales and purchase invoices, bank statements\nLabour contracts and payroll data\nPrevious tax returns and financial statements (if any)",
				'process'   => "Assessment | Review your transactions, invoices and reporting needs | 1 day\nEngagement | Fixed monthly fee, confidentiality agreement | Start of period\nMonthly work | Receive documents, record transactions, file tax returns | Every month\nReporting | Management reports in English | Monthly / quarterly",
				'faq'       => "Can you report in English? | Yes. Statutory reports follow Vietnamese standards; management reports are prepared in English.\nDo representative offices need accounting? | Representative offices do not do business, but still file personal income tax for staff and periodic reports.",
				'content'   => array(
					'Accounting for foreign-invested companies' => 'Foreign-invested companies must keep books under Vietnamese Accounting Standards, file tax returns monthly or quarterly and prepare annual financial statements. We take care of the whole cycle so you can focus on your business.',
				),
			),
			array(
				'slug'      => 'trademark-registration-vietnam',
				'vi'        => 'dang-ky-nhan-hieu',
				'group'     => 'other-services',
				'short'     => 'Trademark registration',
				'title'     => 'Trademark registration in Vietnam',
				'excerpt'   => 'Protect your brand name and logo in Vietnam: clearance search, filing with the Intellectual Property Office, prosecution and certificate delivery.',
				'subtitle'  => 'Clearance search, filing and follow-up until your certificate is granted',
				'price'     => 'Contact us',
				'duration'  => 'Per IP Office examination time',
				'icon'      => 'trademark',
				'includes'  => "Preliminary clearance search\nClassifying goods and services (Nice classification)\nPreparing and filing the application\nResponding to office actions\nDelivering the registration certificate",
				'documents' => "Trademark sample (logo / word mark)\nList of goods and services\nApplicant details (company certificate or passport)\nPower of attorney",
				'process'   => "Clearance search | Assess registrability before filing | 1 – 2 days\nPrepare application | Mark sample, goods and services list | 1 day\nFile & follow up | Formality and substantive examination, publication | Per IP Office timeline\nCertificate | Valid for 10 years, renewable | On grant",
				'faq'       => "How long is a trademark valid in Vietnam? | 10 years from the filing date, renewable for successive 10-year periods.\nVietnam follows the first-to-file rule | Yes – file as early as possible to secure your rights.",
				'content'   => array(
					'Why register your trademark in Vietnam?' => 'Vietnam follows the first-to-file principle: the first applicant generally gets the right. Registering early prevents others from using or registering your brand and lets you enforce your rights.',
				),
			),
		),
	);
}

/**
 * Bài viết lớn của nhóm tiếng Anh (hiện ở trang nhóm).
 *
 * @return array slug nhóm => HTML.
 */
function sgd_en_group_articles() {
	return array(
		'company-formation' => '
<p>Vietnam welcomes foreign investment in most sectors. Depending on your plans you can set up a foreign-invested company (100% foreign-owned or joint venture) or open a representative office. This guide explains the options, the documents you need, the timeline and what to do after licensing.</p>
<h2>Company or representative office?</h2>
<p>A <strong>foreign-invested company</strong> can trade, invoice, hire staff and earn revenue in Vietnam. A <strong>representative office</strong> is a lower-cost presence for market research and partner support, but cannot do business directly.</p>
<h2>Key decisions before filing</h2>
<h3>Business lines and market access</h3>
<p>Some sectors are open without conditions, others require a minimum foreign-ownership limit, a licence or specific capital. We check your business lines against Vietnam\'s market-access list before preparing the file.</p>
<h3>Charter capital</h3>
<p>Capital should match your project and operating costs. It must be contributed within the deadline stated in law, usually 90 days from the date the enterprise certificate is issued.</p>
<h3>Office address</h3>
<p>You need a lease agreement for a commercial address in Vietnam. Residential apartments cannot be used as a head office.</p>
<h2>Fees</h2>
[sgd_price_table group="company-formation" title="Company formation fees" note="Government fees, translation and legalisation are quoted separately. Contact us for a fixed quote for your project."]
<h2>Process and timeline</h2>
[sgd_steps]
Consulting | Review business lines, capital and structure; fixed quote | 1 – 2 days
Investment certificate (IRC) | Prepare and file the investment application | Per authority\'s processing time
Enterprise certificate (ERC) | File the company registration after the IRC | 3 working days
Post-licensing | Seal, capital account, tax registration, capital contribution | After licensing
[/sgd_steps]
<h2>After licensing</h2>
<ul>
<li>Open a direct investment capital account and contribute capital on time.</li>
<li>Register for e-invoices and a digital signature, file the initial tax registration.</li>
<li>Set up bookkeeping and monthly or quarterly tax filing.</li>
</ul>',
		'accounting-tax'    => '
<p>Every company in Vietnam – including foreign-invested companies – must keep accounting records under Vietnamese Accounting Standards, file tax returns on time and prepare annual financial statements. Our team handles the full cycle and reports to you in English.</p>
<h2>What we do every month</h2>
<ul>
<li>Check invoices and supporting documents.</li>
<li>Record transactions and keep the statutory books.</li>
<li>File VAT and personal income tax returns; estimate corporate income tax.</li>
<li>Payroll and social insurance declarations.</li>
</ul>
<h2>Key tax deadlines</h2>
<table>
<caption>Tax filing deadlines in Vietnam</caption>
<thead><tr><th>Filing</th><th>Deadline</th></tr></thead>
<tbody>
<tr><td>Monthly tax return</td><td>20th day of the following month</td></tr>
<tr><td>Quarterly tax return</td><td>Last day of the first month of the following quarter</td></tr>
<tr><td>Annual finalisation and financial statements</td><td>Last day of the 3rd month after the fiscal year end</td></tr>
</tbody>
</table>
<p><em>Deadlines may change with new regulations.</em></p>
<h2>Fees</h2>
[sgd_price_table group="accounting-tax" title="Accounting and tax fees"]
<h2>How we work</h2>
[sgd_steps]
Assessment | Review your transactions and reporting needs | 1 day
Engagement | Fixed monthly fee and confidentiality agreement | Start of period
Monthly work | Bookkeeping and tax filing before each deadline | Every month
Reporting | Management reports in English | Monthly / quarterly
[/sgd_steps]',
		'other-services'    => '
<p>Beyond formation and accounting, businesses in Vietnam need to protect their brand and stay compliant. We handle trademark registration and other administrative services end to end.</p>
<h2>Trademark registration</h2>
<p>Vietnam follows the first-to-file rule. Registering your trademark early with the Intellectual Property Office of Vietnam protects your brand name and logo for 10 years, renewable.</p>
<h2>Fees</h2>
[sgd_price_table group="other-services" title="Fees"]
<h2>Process</h2>
[sgd_steps]
Clearance search | Assess registrability before filing | 1 – 2 days
Prepare application | Mark sample and goods / services list | 1 day
File & follow up | Examination and publication | Per IP Office timeline
Certificate | Valid for 10 years, renewable | On grant
[/sgd_steps]',
	);
}

/**
 * Trang chủ tiếng Anh.
 *
 * @return string
 */
function sgd_en_home_content() {
	return '[section label="1. Banner (H1) + form" bg_color="#0b2a5b" padding="56px" padding__sm="28px" class="sgd-heroband"]
[row]
[col span="12"]
[sgd_hero popular="0" h1="Company formation, accounting and tax services in Vietnam"]
[/col]
[/row]
[/section]
[section label="2. Commitments" bg_color="#ffffff" padding="0px" class="sgd-gnav-sec"]
[row]
[col span="12"]
[sgd_commit style="float"]
wallet | Fixed fees | All-inclusive quote, no extra charges beyond the contract
clock | On time | Every filing done by the agreed deadline
shield | Confidential | Your documents and data kept private
users | In English | A dedicated consultant who speaks your language
[/sgd_commit]
[/col]
[/row]
[/section]
[section label="3. About" bg_color="#ffffff" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_about points="Fixed, transparent fees in every quote
Documents checked before filing – fewer delays
One consultant from start to finish
Reports and updates in English"]
[/col]
[/row]
[/section]
[section label="4. Company formation" bg_color="#f4f7fc" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_group_section group="company-formation" title="Company formation in Vietnam" price="0" posts="0"]
[/col]
[/row]
[/section]
[section label="5. Accounting & tax" bg_color="#ffffff" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_group_section group="accounting-tax" title="Accounting & tax services" flip="1" price="0" posts="0"]
[/col]
[/row]
[/section]
[section label="6. Trademark & other services" bg_color="#f4f7fc" padding="64px" padding__sm="40px"]
[row]
[col span="12"]
[sgd_group_section group="other-services" title="Trademark & other services" price="0" posts="0"]
[/col]
[/row]
[/section]
[section label="7. Process" bg_color="#0b2a5b" dark="true" padding="72px" padding__sm="44px" class="sgd-navyband"]
[row]
[col span="12"]
[sgd_title text="How we work – 4 steps" sub="Clear at every stage – you always know where your application stands." class="is-light"]
[sgd_steps layout="row"]
Free consultation | We review your plan and send a fixed quote | Within 1 day
Prepare documents | We draft every form; you sign abroad or in Vietnam | 1 – 2 days
File & follow up | Online filing, progress updates by email or Zalo | Per authority\'s timeline
Handover | Certificates, seal and your compliance checklist | On issuance
[/sgd_steps]
[/col]
[/row]
[/section]
[section label="8. FAQ + form" bg_color="#f4f7fc" padding="72px" padding__sm="44px"]
[row]
[col span="7" span__sm="12"]
[sgd_title text="Frequently asked questions" class="is-left"]
[sgd_faq]
Can a foreigner own 100% of a company in Vietnam? | Yes, in most sectors. Some business lines have foreign-ownership limits or conditions; we check them before filing.
How long does it take to set up a company? | Typically 4 – 8 weeks for the investment and enterprise certificates, depending on your business lines and the authority\'s processing time.
Do I need to come to Vietnam? | Not necessarily. Documents can be signed and legalised abroad; we file online and keep you updated.
What is the minimum capital? | Most sectors have no fixed minimum, but capital must match your project. Conditional sectors may have specific requirements.
Do you provide accounting after setup? | Yes – monthly bookkeeping, tax filing, payroll and annual financial statements, with reports in English.
[/sgd_faq]
[/col]
[col span="5" span__sm="12"]
<div class="sgd-sticky-form">[sgd_lead_form source="Home EN – FAQ" perks="1" title="Still have questions? Ask a consultant"]</div>
[/col]
[/row]
[/section]
[section label="9. Call to action" bg_color="#ffffff" padding="0px"]
[row]
[col span="12"]
[sgd_cta_strip title="Need advice now? Talk to us" sub="Free consultation and a fixed quote within one business day."]
[gap height="56px"]
[/col]
[/row]
[/section]';
}

/**
 * Trang About us.
 *
 * @return string
 */
function sgd_en_about_content() {
	$c = esc_html( sgd_defaults_en()['company'] );
	return '[sgd_pagehead title="About ' . $c . '" sub="Company formation, accounting and tax services in Vietnam."]
[section bg_color="#ffffff" padding="60px" padding__sm="36px"]
[row]
[col span="12"]
[sgd_about]
[/col]
[/row]
[/section]
[section bg_color="#ffffff" padding="60px" padding__sm="36px"]
[row]
[col span="7" span__sm="12" class="sgd-prose"]
<h2>Who we are</h2>
<p>' . $c . ' provides corporate legal, accounting and tax services to foreign investors, small and medium-sized businesses and start-ups in Vietnam. We support you from the first idea – choosing the right structure and obtaining your licences – to monthly accounting, tax filing and changes throughout the life of your company.</p>
<h2>What we do</h2>
<ul>
<li><strong>Company formation:</strong> foreign-invested companies, joint ventures, representative offices.</li>
<li><strong>Accounting &amp; tax:</strong> bookkeeping, tax returns, payroll, financial statements, tax finalisation.</li>
<li><strong>Trademark &amp; compliance:</strong> trademark registration, e-invoices, digital signatures.</li>
</ul>
<h2>How we work</h2>
[sgd_steps]
Listen and advise | Free consultation on the rules that apply to you | Within 1 day
Fixed quote | Service and government fees stated clearly, no hidden costs | Same day
Do the work | We prepare and file everything, with regular updates | Per procedure
Handover and support | Results delivered, next steps explained | After completion
[/sgd_steps]
[/col]
[col span="5" span__sm="12"]
<div id="dang-ky"></div>
[sgd_lead_form source="About us EN"]
[/col]
[/row]
[/section]';
}

/**
 * Trang Contact us.
 *
 * @return string
 */
function sgd_en_contact_content() {
	$map = 'https://www.google.com/maps?q=' . rawurlencode( sgd_opt( 'address' ) ) . '&output=embed';
	return '[sgd_pagehead title="Contact us" sub="Call, message us on Zalo or send a request – a consultant will get back to you shortly."]
[section bg_color="#ffffff" padding="50px"]
[row]
[col span="6" span__sm="12"]
<h2>Get in touch</h2>
[sgd_contact_list]
[sgd_call_buttons]
[gap height="20px"]
<div class="sgd-map__frame"><iframe src="' . esc_url( $map ) . '" title="Office map" loading="lazy" allowfullscreen></iframe></div>
[/col]
[col span="6" span__sm="12"]
<div id="dang-ky"></div>
[sgd_lead_form title="Send us a request" source="Contact EN"]
[/col]
[/row]
[/section]';
}

/**
 * Footer tiếng Anh.
 *
 * @param array $groups slug => term ID (EN).
 * @param array $pages  about, contact => ID.
 * @return string
 */
function sgd_en_footer_content( $groups, $pages ) {
	$g = '';
	foreach ( $groups as $tid ) {
		$t = get_term( $tid, 'nhom_dich_vu' );
		if ( $t && ! is_wp_error( $t ) ) {
			$g .= '<li><a href="' . esc_url( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</a></li>';
		}
	}
	$p = '';
	foreach ( array( 'about' => 'About us', 'contact' => 'Contact us' ) as $k => $label ) {
		if ( ! empty( $pages[ $k ] ) ) {
			$p .= '<li><a href="' . esc_url( get_permalink( $pages[ $k ] ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}
	$en = sgd_defaults_en();
	return '[section bg_color="#0b2a5b" dark="true" padding="56px" padding__sm="36px" class="sgd-footer--dark"]
[row]
[col span="4" span__sm="12"]
<img class="sgd-footer__brandlogo" src="' . esc_url( SGD_URI . '/assets/img/logo-119-white.svg' ) . '" alt="' . esc_attr( $en['company'] ) . '" width="260" height="52" loading="lazy">
<p class="sgd-footer__about">' . esc_html( $en['company_full'] ) . ' – company formation, accounting and tax services in Vietnam for foreign investors and local businesses.</p>
[sgd_contact_list]
[/col]
[col span="3" span__sm="6"]
<p class="sgd-footer__h">Services</p>
<ul class="sgd-flinks">' . $g . '</ul>
[/col]
[col span="2" span__sm="6"]
<p class="sgd-footer__h">Information</p>
<ul class="sgd-flinks">' . $p . '</ul>
[/col]
[col span="3" span__sm="12"]
<p class="sgd-footer__h">Free consultation</p>
<p class="sgd-footer__hot"><a href="tel:' . esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ) . '">' . esc_html( sgd_opt( 'hotline' ) ) . '</a></p>
<p class="sgd-footer__small">Call or message us on Zalo – ' . esc_html( $en['working_hours'] ) . '</p>
[sgd_call_buttons]
[/col]
[/row]
[/section]';
}

/**
 * Tạo 2 ngôn ngữ nếu chưa có (Tiếng Việt mặc định, English).
 *
 * @return bool
 */
function sgd_en_languages() {
	if ( ! function_exists( 'PLL' ) || ! function_exists( 'pll_languages_list' ) ) {
		return false;
	}
	$have  = (array) pll_languages_list();
	$model = PLL()->model;
	$add   = function ( $args ) use ( $model ) {
		if ( isset( $model->languages ) && is_object( $model->languages ) && method_exists( $model->languages, 'add' ) ) {
			return $model->languages->add( $args );
		}
		return method_exists( $model, 'add_language' ) ? $model->add_language( $args ) : false;
	};
	if ( ! in_array( 'vi', $have, true ) ) {
		$add( array( 'name' => 'Tiếng Việt', 'slug' => 'vi', 'locale' => 'vi', 'rtl' => false, 'term_group' => 0, 'flag' => 'vn' ) );
	}
	if ( ! in_array( 'en', $have, true ) ) {
		$add( array( 'name' => 'English', 'slug' => 'en', 'locale' => 'en_US', 'rtl' => false, 'term_group' => 1, 'flag' => 'gb' ) );
	}
	if ( isset( $model->languages ) && is_object( $model->languages ) && method_exists( $model->languages, 'clean_cache' ) ) {
		$model->languages->clean_cache();
	} elseif ( method_exists( $model, 'clean_languages_cache' ) ) {
		$model->clean_languages_cache();
	}
	// Tiếng Việt là ngôn ngữ mặc định (URL không có /vi/); trang chủ tiếng Anh nằm ở /en/ (không phải /en/home/).
	$opt = PLL()->options;
	if ( 'vi' !== pll_default_language() ) {
		if ( isset( $model->languages ) && is_object( $model->languages ) && method_exists( $model->languages, 'update_default' ) ) {
			$model->languages->update_default( 'vi' );
		} elseif ( is_object( $opt ) && method_exists( $opt, 'set' ) ) {
			$opt->set( 'default_lang', 'vi' );
		}
	}
	if ( is_object( $opt ) && method_exists( $opt, 'set' ) ) {
		$opt->set( 'redirect_lang', true );
		$opt->set( 'hide_default', true );
		$opt->set( 'force_lang', 1 );
		if ( method_exists( $opt, 'save' ) ) {
			$opt->save();
		}
	}
	sgd_en_fix_mislabeled();
	// Nội dung cũ chưa có ngôn ngữ → Tiếng Việt.
	$vi = method_exists( $model, 'get_language' ) ? $model->get_language( 'vi' ) : null;
	if ( method_exists( $model, 'set_language_in_mass' ) ) {
		$model->set_language_in_mass( $vi ? $vi : null );
	}
	$list = (array) pll_languages_list();
	return in_array( 'vi', $list, true ) && in_array( 'en', $list, true );
}

/**
 * Sửa nội dung tiếng Việt bị gán nhầm "English" (xảy ra khi trình hướng dẫn Polylang chọn English làm mặc định
 * và gán toàn bộ nội dung cũ cho English): mọi bài / trang / dịch vụ / nhóm / chuyên mục đang là English
 * mà không phải nội dung tiếng Anh của theme (hoặc bản dịch tiếng Anh bạn tự tạo) → Tiếng Việt.
 */
function sgd_en_fix_mislabeled() {
	$data      = sgd_en_data();
	$en_posts  = array_merge( wp_list_pluck( $data['services'], 'slug' ), array( 'home', 'about-us', 'contact-us', 'footer-website-en' ) );
	$en_terms  = array_keys( $data['groups'] );
	$vi_chars  = '/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu';
	$types     = array_values( array_filter( array( 'post', 'page', 'dich_vu', 'blocks' ), 'pll_is_translated_post_type' ) );
	$ids       = get_posts( array( 'post_type' => $types, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'lang' => 'en', 'suppress_filters' => false ) );
	foreach ( $ids as $id ) {
		$p = get_post( $id );
		if ( ! $p || 'en' !== pll_get_post_language( $id ) || in_array( $p->post_name, $en_posts, true ) ) {
			continue;
		}
		$tr = pll_get_post_translations( $id );
		// Bài tiếng Anh bạn tự tạo (tiêu đề không dấu và đã nối với 1 bài tiếng Việt khác) → giữ nguyên.
		if ( ! preg_match( $vi_chars, $p->post_title ) && ! empty( $tr['vi'] ) && (int) $tr['vi'] !== (int) $id ) {
			continue;
		}
		pll_set_post_language( $id, 'vi' );
	}
	$taxes = array_values( array_filter( array( 'category', 'post_tag', 'nhom_dich_vu' ), 'pll_is_translated_taxonomy' ) );
	$terms = get_terms( array( 'taxonomy' => $taxes, 'hide_empty' => false, 'lang' => 'en' ) );
	foreach ( is_array( $terms ) ? $terms : array() as $t ) {
		if ( 'en' === pll_get_term_language( $t->term_id ) && ! in_array( $t->slug, $en_terms, true ) && preg_match( $vi_chars, $t->name ) ) {
			pll_set_term_language( $t->term_id, 'vi' );
		}
	}
}

/**
 * Dựng bản tiếng Anh.
 *
 * @return string '' nếu xong, hoặc thông báo lỗi.
 */
function sgd_demo_build_en() {
	if ( ! function_exists( 'pll_set_post_language' ) ) {
		return 'nopll';
	}
	if ( ! sgd_en_languages() ) {
		return 'nolang';
	}
	sgd_register_services();
	$data = sgd_en_data();

	// 1. Nhóm tiếng Anh, nối với nhóm tiếng Việt.
	$groups = array();
	foreach ( $data['groups'] as $slug => $g ) {
		$id = sgd_demo_term( 'nhom_dich_vu', $g[0], $slug, $g[3], array( '_sgd_icon' => $g[1], '_sgd_order' => $g[2] ) );
		if ( ! $id ) {
			continue;
		}
		pll_set_term_language( $id, 'en' );
		$groups[ $slug ] = $id;
		$vi              = get_term_by( 'slug', $g[4], 'nhom_dich_vu' );
		if ( $vi && ! is_wp_error( $vi ) ) {
			pll_set_term_language( $vi->term_id, 'vi' );
			pll_save_term_translations( array( 'vi' => $vi->term_id, 'en' => $id ) );
		}
	}

	// 2. Dịch vụ tiếng Anh.
	$services = array();
	foreach ( $data['services'] as $i => $s ) {
		$content = '';
		foreach ( $s['content'] as $h => $p ) {
			$content .= '<h2>' . esc_html( $h ) . "</h2>\n<p>" . esc_html( $p ) . "</p>\n";
		}
		$id = sgd_demo_post(
			array(
				'post_type'    => 'dich_vu',
				'post_name'    => $s['slug'],
				'post_title'   => $s['title'],
				'post_excerpt' => $s['excerpt'],
				'post_content' => $content,
				'menu_order'   => $i + 1,
			),
			array(
				'_sgd_demo'      => 'service-en',
				'_sgd_short'     => $s['short'],
				'_sgd_subtitle'  => $s['subtitle'],
				'_sgd_price'     => $s['price'],
				'_sgd_duration'  => $s['duration'],
				'_sgd_icon'      => $s['icon'],
				'_sgd_includes'  => $s['includes'],
				'_sgd_documents' => $s['documents'],
				'_sgd_process'   => $s['process'],
				'_sgd_packages'  => '',
				'_sgd_costs'     => '',
				'_sgd_price_table' => '',
				'_sgd_faq'       => $s['faq'],
				'_sgd_featured'  => empty( $s['featured'] ) ? '' : '1',
			)
		);
		if ( ! $id ) {
			continue;
		}
		pll_set_post_language( $id, 'en' );
		if ( isset( $groups[ $s['group'] ] ) ) {
			wp_set_object_terms( $id, array( $groups[ $s['group'] ] ), 'nhom_dich_vu' );
		}
		$services[ $s['slug'] ] = $id;
		$vi                     = get_page_by_path( $s['vi'], OBJECT, 'dich_vu' );
		if ( $vi ) {
			if ( ! pll_get_post_language( $vi->ID ) || 'en' === pll_get_post_language( $vi->ID ) ) {
				pll_set_post_language( $vi->ID, 'vi' );
			}
			pll_save_post_translations( array( 'vi' => $vi->ID, 'en' => $id ) );
		}
	}

	// 3. Bài viết lớn của nhóm.
	foreach ( sgd_en_group_articles() as $slug => $html ) {
		if ( isset( $groups[ $slug ] ) && '' === trim( (string) get_term_meta( $groups[ $slug ], '_sgd_article', true ) ) ) {
			update_term_meta( $groups[ $slug ], '_sgd_article', $html );
		}
	}

	// 4. Trang.
	$blank = array( '_wp_page_template' => 'page-blank.php' );
	$pages = array(
		'about'   => array( 'about-us', 'About us', sgd_en_about_content(), 'gioi-thieu' ),
		'contact' => array( 'contact-us', 'Contact us', sgd_en_contact_content(), 'lien-he' ),
		'home'    => array( 'home', 'Home', sgd_en_home_content(), '' ),
	);
	$ids   = array();
	foreach ( $pages as $k => $pg ) {
		$id = sgd_demo_post( array( 'post_type' => 'page', 'post_name' => $pg[0], 'post_title' => $pg[1], 'post_content' => $pg[2] ), $blank );
		if ( ! $id ) {
			continue;
		}
		pll_set_post_language( $id, 'en' );
		$ids[ $k ] = $id;
		$vi_id     = 'home' === $k ? (int) get_option( 'page_on_front' ) : ( get_page_by_path( $pg[3] ) ? get_page_by_path( $pg[3] )->ID : 0 );
		if ( $vi_id && $vi_id !== $id ) {
			pll_set_post_language( $vi_id, 'vi' );
			pll_save_post_translations( array( 'vi' => $vi_id, 'en' => $id ) );
		}
	}

	// 5. Footer tiếng Anh.
	$existing = get_page_by_path( 'footer-website-en', OBJECT, 'blocks' );
	wp_insert_post(
		array(
			'ID'           => $existing ? $existing->ID : 0,
			'post_type'    => 'blocks',
			'post_status'  => 'publish',
			'post_name'    => 'footer-website-en',
			'post_title'   => 'Footer website (English)',
			'post_content' => wp_slash( sgd_en_footer_content( $groups, $ids ) ),
		)
	);

	// 6. Menu tiếng Anh.
	sgd_en_menu( $groups, $services, $ids );

	// 7. Xoá bộ nhớ đệm ngôn ngữ của Polylang (lưu sẵn trang chủ từng ngôn ngữ) để /en/ nhận trang chủ tiếng Anh ngay.
	$model = PLL()->model;
	if ( isset( $model->languages ) && is_object( $model->languages ) && method_exists( $model->languages, 'clean_cache' ) ) {
		$model->languages->clean_cache();
	} elseif ( method_exists( $model, 'clean_languages_cache' ) ) {
		$model->clean_languages_cache();
	}
	if ( isset( PLL()->static_pages ) && is_object( PLL()->static_pages ) && method_exists( PLL()->static_pages, 'clean_cache' ) ) {
		PLL()->static_pages->clean_cache();
	}

	if ( function_exists( 'flush_rewrite_rules' ) ) {
		flush_rewrite_rules();
	}
	return '';
}

/**
 * Menu tiếng Anh + gán vị trí menu theo ngôn ngữ trong Polylang.
 *
 * @param array $groups   slug => term ID.
 * @param array $services slug => post ID.
 * @param array $pages    about, contact, home => ID.
 */
function sgd_en_menu( $groups, $services, $pages ) {
	$name    = 'Main menu (English)';
	$menu    = wp_get_nav_menu_object( $name );
	$menu_id = $menu ? $menu->term_id : wp_create_nav_menu( $name );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) {
		wp_delete_post( $it->ID, true );
	}
	$add  = function ( $title, $args, $parent = 0 ) use ( $menu_id ) {
		return wp_update_nav_menu_item( $menu_id, 0, array_merge( array( 'menu-item-title' => $title, 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent ), $args ) );
	};
	$page = function ( $id ) {
		return array( 'menu-item-object' => 'page', 'menu-item-object-id' => $id, 'menu-item-type' => 'post_type' );
	};
	$tax  = function ( $slug ) use ( $groups ) {
		return array( 'menu-item-object' => 'nhom_dich_vu', 'menu-item-object-id' => $groups[ $slug ], 'menu-item-type' => 'taxonomy' );
	};
	$svc  = function ( $slug ) use ( $services ) {
		return array( 'menu-item-object' => 'dich_vu', 'menu-item-object-id' => $services[ $slug ], 'menu-item-type' => 'post_type' );
	};
	if ( ! empty( $pages['about'] ) ) {
		$add( 'About us', $page( $pages['about'] ) );
	}
	$tree = array(
		'company-formation' => array( 'Company formation', array( 'fdi-company-establishment' => 'FDI company', 'representative-office-vietnam' => 'Representative office' ) ),
		'accounting-tax'    => array( 'Accounting & tax', array( 'tax-and-accounting-service' => 'Tax and accounting service' ) ),
		'other-services'    => array( 'Trademark', array( 'trademark-registration-vietnam' => 'Trademark registration' ) ),
	);
	foreach ( $tree as $g => $node ) {
		if ( empty( $groups[ $g ] ) ) {
			continue;
		}
		$parent = $add( $node[0], $tax( $g ) );
		$add( get_term( $groups[ $g ] )->name, $tax( $g ), $parent );
		foreach ( $node[1] as $slug => $label ) {
			if ( ! empty( $services[ $slug ] ) ) {
				$add( $label, $svc( $slug ), $parent );
			}
		}
	}
	if ( ! empty( $pages['contact'] ) ) {
		$add( 'Contact us', $page( $pages['contact'] ) );
	}

	// Polylang lưu menu theo từng ngôn ngữ: giữ menu tiếng Việt đang dùng, thêm menu tiếng Anh.
	if ( function_exists( 'PLL' ) && isset( PLL()->options ) ) {
		$theme = get_option( 'stylesheet' );
		$locs  = get_theme_mod( 'nav_menu_locations', array() );
		$opt   = PLL()->options;
		$nav   = is_object( $opt ) && method_exists( $opt, 'get' ) ? (array) $opt->get( 'nav_menus' ) : (array) ( $opt['nav_menus'] ?? array() );
		$vi_menu = wp_get_nav_menu_object( 'Menu chính' );
		foreach ( array( 'primary', 'primary_mobile' ) as $loc ) {
			if ( $vi_menu ) {
				$nav[ $theme ][ $loc ]['vi'] = (int) $vi_menu->term_id;
			} elseif ( ! empty( $locs[ $loc ] ) && (int) $locs[ $loc ] !== (int) $menu_id && empty( $nav[ $theme ][ $loc ]['vi'] ) ) {
				$nav[ $theme ][ $loc ]['vi'] = (int) $locs[ $loc ];
			}
			$nav[ $theme ][ $loc ]['en'] = (int) $menu_id;
		}
		// Vị trí menu lưu trong theme là của ngôn ngữ mặc định (Tiếng Việt).
		if ( $vi_menu ) {
			$locs['primary']        = (int) $vi_menu->term_id;
			$locs['primary_mobile'] = (int) $vi_menu->term_id;
			set_theme_mod( 'nav_menu_locations', $locs );
		}
		if ( is_object( $opt ) && method_exists( $opt, 'set' ) ) {
			$opt->set( 'nav_menus', $nav );
			if ( method_exists( $opt, 'save' ) ) {
				$opt->save();
			}
		} else {
			$all              = get_option( 'polylang', array() );
			$all['nav_menus'] = $nav;
			update_option( 'polylang', $all );
		}
	}
}

/**
 * Nút trong trang Tạo site mẫu.
 */
function sgd_en_admin_box() {
	$msg = isset( $_GET['sgd_demo'] ) ? sanitize_key( wp_unslash( $_GET['sgd_demo'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap" style="margin-top:24px;padding:16px 20px;background:#fff;border:1px solid #dcdcde;max-width:900px"><h2 style="margin-top:0">Bản tiếng Anh (English)</h2>';
	if ( 'en' === $msg ) {
		echo '<div class="notice notice-success inline"><p><strong>Đã tạo bản tiếng Anh.</strong> <a href="' . esc_url( function_exists( 'pll_home_url' ) ? pll_home_url( 'en' ) : home_url( '/en/' ) ) . '" target="_blank">Xem trang chủ tiếng Anh</a></p></div>';
	} elseif ( 'nopll' === $msg || 'nolang' === $msg ) {
		echo '<div class="notice notice-error inline"><p>Chưa tạo được: hãy cài và kích hoạt plugin <strong>Polylang</strong> (miễn phí) rồi bấm lại.</p></div>';
	}
	if ( ! function_exists( 'pll_set_post_language' ) ) {
		echo '<p>Cần plugin <strong>Polylang</strong>: <a href="' . esc_url( admin_url( 'plugin-install.php?s=polylang&tab=search&type=term' ) ) . '">Plugin → Cài mới → tìm "Polylang"</a> → Cài đặt → Kích hoạt (bỏ qua trình hướng dẫn của Polylang cũng được).</p>';
	}
	echo '<p>Nút này tạo: ngôn ngữ Tiếng Việt (mặc định) + English ở <code>/en/</code>; 3 nhóm và 4 dịch vụ cho khách nước ngoài (FDI company, Representative office, Tax &amp; accounting, Trademark); trang chủ, About us, Contact us; footer và menu tiếng Anh; nút VI | EN trên header. Nội dung tiếng Việt giữ nguyên. Chữ cố định của theme tự chuyển tiếng Anh.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'Tạo / cập nhật bản tiếng Anh?\');"><input type="hidden" name="action" value="sgd_demo_en_run">';
	wp_nonce_field( 'sgd_demo_en_run' );
	submit_button( 'Tạo bản tiếng Anh', 'primary', 'submit', false );
	echo '</form></div>';
}
add_action( 'sgd_demo_page_after', 'sgd_en_admin_box' );

/**
 * Chạy dựng bản tiếng Anh.
 */
function sgd_demo_en_run() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Không có quyền.' );
	}
	check_admin_referer( 'sgd_demo_en_run' );
	$err = sgd_demo_build_en();
	wp_safe_redirect( admin_url( 'themes.php?page=sgd-demo&sgd_demo=' . ( $err ? $err : 'en' ) ) );
	exit;
}
add_action( 'admin_post_sgd_demo_en_run', 'sgd_demo_en_run' );
