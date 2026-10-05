<?php
/**
 * SEO: breadcrumb, dữ liệu cấu trúc (schema), mô tả dự phòng.
 * Hoạt động cùng Rank Math: Rank Math lo tiêu đề / mô tả / sitemap / Organization / Breadcrumb
 * (nếu bật), theme bổ sung schema Service + bảng giá (Offer) + FAQPage + danh sách dịch vụ.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Có plugin SEO lo phần meta không.
 *
 * @return bool
 */
function sgd_has_seo_plugin() {
	return defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Rank Math đang bật breadcrumb không.
 *
 * @return bool
 */
function sgd_rank_math_breadcrumbs() {
	return function_exists( 'rank_math_the_breadcrumbs' ) && class_exists( '\RankMath\Helper' ) && \RankMath\Helper::get_settings( 'general.breadcrumbs' );
}

/**
 * Chuỗi breadcrumb cho trang hiện tại: [[tên, url], ...].
 *
 * @return array
 */
function sgd_breadcrumb_trail() {
	$trail   = array( array( 'Trang chủ', home_url( '/' ) ) );
	$archive = array( 'Dịch vụ', get_post_type_archive_link( 'dich_vu' ) );
	if ( is_singular( 'dich_vu' ) ) {
		$trail[] = $archive;
		$group   = sgd_first_group();
		if ( $group ) {
			$trail[] = array( $group->name, get_term_link( $group ) );
		}
		$trail[] = array( get_the_title(), get_permalink() );
	} elseif ( is_post_type_archive( 'dich_vu' ) ) {
		$trail[] = $archive;
	} elseif ( is_singular( 'post' ) || is_home() || is_category() || is_tag() || is_search() ) {
		$blog_id = (int) get_option( 'page_for_posts' );
		$blog    = array( $blog_id ? get_the_title( $blog_id ) : 'Kiến thức', $blog_id ? get_permalink( $blog_id ) : home_url( '/' ) );
		$trail[] = $blog;
		if ( is_singular( 'post' ) ) {
			$cats = get_the_category();
			if ( $cats ) {
				$trail[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
			}
			$trail[] = array( get_the_title(), get_permalink() );
		} elseif ( is_category() || is_tag() ) {
			$trail[] = array( single_term_title( '', false ), get_term_link( get_queried_object() ) );
		} elseif ( is_search() ) {
			$trail[] = array( 'Tìm kiếm', home_url( '/?s=' . rawurlencode( get_search_query() ) ) );
		}
	} elseif ( is_tax( 'nhom_dich_vu' ) ) {
		$term    = get_queried_object();
		$trail[] = $archive;
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $anc ) {
			$a       = get_term( $anc, $term->taxonomy );
			$trail[] = array( $a->name, get_term_link( $a ) );
		}
		$trail[] = array( $term->name, get_term_link( $term ) );
	} elseif ( is_page() && ! is_front_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $anc ) {
			$trail[] = array( get_the_title( $anc ), get_permalink( $anc ) );
		}
		$trail[] = array( get_the_title(), get_permalink() );
	}
	return $trail;
}

/**
 * In breadcrumb (dùng Rank Math nếu đang bật).
 */
function sgd_breadcrumbs() {
	if ( sgd_rank_math_breadcrumbs() ) {
		echo '<div class="sgd-breadcrumb">';
		rank_math_the_breadcrumbs();
		echo '</div>';
		return;
	}
	$trail = sgd_breadcrumb_trail();
	$last  = count( $trail ) - 1;
	echo '<nav class="sgd-breadcrumb" aria-label="Breadcrumb"><ol>';
	foreach ( $trail as $i => $t ) {
		echo '<li>' . ( $i < $last ? '<a href="' . esc_url( $t[1] ) . '">' . esc_html( $t[0] ) . '</a>' : '<span aria-current="page">' . esc_html( $t[0] ) . '</span>' ) . '</li>';
	}
	echo '</ol></nav>';
}

/**
 * Noindex trang có thông báo form (?sgd_status=…).
 *
 * @param array $robots Robots.
 * @return array
 */
function sgd_robots( $robots ) {
	// Chuyên mục chưa có bài (thẻ "Đang cập nhật" trên trang Kiến thức) – không cho Google lập chỉ mục trang trống.
	if ( isset( $_GET['sgd_status'] ) || ( is_category() && ! have_posts() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'sgd_robots' );

/**
 * Như trên cho Rank Math.
 *
 * @param array $robots Robots.
 * @return array
 */
function sgd_rank_math_robots( $robots ) {
	if ( isset( $_GET['sgd_status'] ) || ( is_category() && ! have_posts() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'sgd_rank_math_robots' );

/**
 * Bỏ UX Blocks của Flatsome khỏi sitemap Rank Math.
 *
 * @param bool   $exclude Loại bỏ?
 * @param string $type    Loại bài.
 * @return bool
 */
function sgd_sitemap_exclude( $exclude, $type ) {
	return 'blocks' === $type ? true : $exclude;
}
add_filter( 'rank_math/sitemap/exclude_post_type', 'sgd_sitemap_exclude', 10, 2 );

/**
 * Mô tả dự phòng khi không có plugin SEO.
 */
function sgd_fallback_meta() {
	if ( sgd_has_seo_plugin() ) {
		return;
	}
	$desc = '';
	if ( is_singular( 'dich_vu' ) ) {
		$desc = has_excerpt() ? get_the_excerpt() : sgd_meta( 'subtitle' );
	} elseif ( is_tax( 'nhom_dich_vu' ) ) {
		$desc = term_description();
	} elseif ( is_post_type_archive( 'dich_vu' ) ) {
		$desc = sgd_opt( 'archive_intro' );
	} elseif ( is_singular( 'post' ) ) {
		$desc = has_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) );
	} elseif ( is_home() || is_category() || is_tag() ) {
		$desc = is_home() ? sgd_opt( 'blog_intro' ) : term_description();
	} elseif ( is_front_page() ) {
		$desc = sgd_opt( 'home_desc' );
		if ( '' === trim( (string) $desc ) ) {
			// Mở đầu bằng dịch vụ (từ khoá), kết bằng hotline – không tốn ký tự cho khẩu hiệu.
			$desc = 'Dịch vụ thành lập công ty, thay đổi giấy phép kinh doanh, kê khai thuế, kế toán trọn gói. Báo giá rõ ràng, không phát sinh.'
				. ( sgd_opt( 'hotline' ) ? ' Hotline ' . sgd_opt( 'hotline' ) . '.' : '' );
		}
	}
	$desc = sgd_clip( wp_strip_all_tags( (string) $desc ), 155 );
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . "\">\n";
	}

	// Open Graph / Twitter (chia sẻ Facebook, Zalo) – plugin SEO đã lo thì bỏ qua.
	$url   = is_singular() ? get_permalink() : ( is_tax() ? get_term_link( get_queried_object() ) : ( is_post_type_archive( 'dich_vu' ) ? get_post_type_archive_link( 'dich_vu' ) : home_url( '/' ) ) );
	$image = is_singular() && has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'large' ) : '';
	if ( ! $image ) {
		$logo  = get_theme_mod( 'site_logo' );
		$image = $logo ? ( is_numeric( $logo ) ? wp_get_attachment_image_url( $logo, 'full' ) : $logo ) : '';
	}
	$tags = array(
		'og:locale'      => 'vi_VN',
		'og:type'        => is_singular( array( 'post' ) ) ? 'article' : 'website',
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:title'       => wp_get_document_title(),
		'og:description' => $desc,
		'og:url'         => is_wp_error( $url ) ? '' : $url,
		'og:image'       => $image,
	);
	foreach ( array_filter( $tags ) as $k => $v ) {
		echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . "\">\n";
	}
	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . "\">\n";
}
add_action( 'wp_head', 'sgd_fallback_meta', 1 );

/**
 * Cắt chuỗi theo ký tự, không cắt giữa từ.
 *
 * @param string $text Chuỗi.
 * @param int    $max  Số ký tự tối đa.
 * @return string
 */
function sgd_clip( $text, $max = 155 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max - 1 );
	$sp  = mb_strrpos( $cut, ' ' );
	return rtrim( $sp ? mb_substr( $cut, 0, $sp ) : $cut, ' ,.;:–-' ) . '…';
}

/**
 * Tiêu đề trang dịch vụ khi không có plugin SEO: "Tên dịch vụ – Giá từ … | Thương hiệu"
 * (giá trong tiêu đề tăng tỉ lệ nhấp trên Google – cả hai website tham khảo đều làm vậy).
 *
 * @param array $parts Các phần tiêu đề.
 * @return array
 */
function sgd_title_parts( $parts ) {
	if ( ! sgd_has_seo_plugin() && is_front_page() && sgd_opt( 'home_title' ) ) {
		// Trang chủ: "Dịch vụ … | Thương hiệu" thay cho "Thương hiệu – Khẩu hiệu" (không có từ khoá).
		return array(
			'title' => sgd_opt( 'home_title' ),
			'site'  => sgd_opt( 'company' ),
		);
	}
	if ( sgd_has_seo_plugin() || ! is_singular( 'dich_vu' ) ) {
		return $parts;
	}
	$price = sgd_meta( 'price', get_queried_object_id() );
	if ( $price && sgd_price_number( $price ) && ! preg_match( '/\d{3}/', $parts['title'] ) ) {
		$parts['title'] .= ' – ' . ( preg_match( '/^(từ|chỉ)\b/iu', $price ) ? $price : 'Giá ' . $price );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'sgd_title_parts' );

/**
 * Gom câu hỏi từ [sgd_faq] trên trang để in FAQPage ở cuối trang.
 *
 * @param string $q Câu hỏi.
 * @param string $a Trả lời.
 */
function sgd_collect_faq( $q, $a ) {
	$GLOBALS['sgd_faq_items'][] = array( wp_strip_all_tags( $q ), wp_strip_all_tags( $a ) );
}

/**
 * In FAQPage cho các câu hỏi gom được (trang chủ, trang thường).
 */
function sgd_faq_schema_footer() {
	if ( empty( $GLOBALS['sgd_faq_items'] ) || is_singular( 'dich_vu' ) ) {
		return;
	}
	$q = array();
	foreach ( $GLOBALS['sgd_faq_items'] as $f ) {
		$q[] = array(
			'@type'          => 'Question',
			'name'           => $f[0],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ),
		);
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $q ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
}
add_action( 'wp_footer', 'sgd_faq_schema_footer', 5 );

/**
 * Đổi "1.500.000đ", "1,5 triệu" → số (VND) cho schema Offer. Không đọc được → 0.
 *
 * @param string $text Giá.
 * @return int
 */
function sgd_price_number( $text ) {
	$t = mb_strtolower( (string) $text );
	if ( preg_match( '/([\d.,]+)\s*(triệu|tr)\b/u', $t, $m ) ) {
		return (int) round( (float) str_replace( ',', '.', str_replace( '.', '', $m[1] ) ) * 1000000 );
	}
	if ( preg_match( '/(\d{1,3}(?:[.,]\d{3})+|\d{4,})/', $t, $m ) ) {
		return (int) preg_replace( '/\D/', '', $m[1] );
	}
	return 0;
}

/**
 * Thông tin doanh nghiệp (schema).
 *
 * @return array
 */
function sgd_org_schema() {
	$org  = array(
		'@type'     => 'ProfessionalService',
		'@id'       => home_url( '/#business' ),
		'name'      => sgd_opt( 'company_full' ),
		'url'       => home_url( '/' ),
		'telephone' => sgd_opt( 'hotline' ),
		'email'     => sgd_opt( 'email' ),
		'address'   => array(
			'@type'          => 'PostalAddress',
			'streetAddress'  => sgd_opt( 'address' ),
			'addressCountry' => 'VN',
		),
		'areaServed' => 'VN',
	);
	$logo = get_theme_mod( 'site_logo' );
	if ( $logo ) {
		$org['logo'] = is_numeric( $logo ) ? wp_get_attachment_image_url( $logo, 'full' ) : $logo;
		$org['image'] = $org['logo'];
	}
	if ( sgd_opt( 'tax_code' ) ) {
		$org['taxID'] = sgd_opt( 'tax_code' );
	}
	$same = array_values( array_filter( array( sgd_opt( 'facebook' ), sgd_opt( 'youtube' ) ) ) );
	if ( $same ) {
		$org['sameAs'] = $same;
	}
	// Danh mục dịch vụ kèm giá (OfferCatalog) – giúp Google hiểu doanh nghiệp bán gì.
	$items = array();
	foreach ( get_posts( array( 'post_type' => 'dich_vu', 'posts_per_page' => 30, 'orderby' => array( 'menu_order' => 'ASC' ), 'no_found_rows' => true ) ) as $p ) {
		$offer = array(
			'@type'       => 'Offer',
			'itemOffered' => array( '@type' => 'Service', 'name' => get_the_title( $p ), 'url' => get_permalink( $p ) ),
		);
		$n = sgd_price_number( sgd_meta( 'price', $p->ID ) );
		if ( $n ) {
			$offer['price']         = $n;
			$offer['priceCurrency'] = 'VND';
		}
		$items[] = $offer;
	}
	$prices = array_filter( array_map( function ( $o ) { return isset( $o['price'] ) ? $o['price'] : 0; }, $items ) );
	if ( $prices ) {
		$org['priceRange'] = number_format( min( $prices ), 0, ',', '.' ) . 'đ – ' . number_format( max( $prices ), 0, ',', '.' ) . 'đ';
	}
	if ( $items ) {
		$org['hasOfferCatalog'] = array( '@type' => 'OfferCatalog', 'name' => 'Dịch vụ', 'itemListElement' => $items );
	}
	return $org;
}

/**
 * JSON-LD.
 */
function sgd_schema() {
	$graph = array();
	if ( ! defined( 'RANK_MATH_VERSION' ) && is_front_page() ) {
		$graph[] = array(
			'@type'      => 'WebSite',
			'@id'        => home_url( '/#website' ),
			'url'        => home_url( '/' ),
			'name'       => get_bloginfo( 'name' ),
			'inLanguage' => 'vi',
			'publisher'  => array( '@id' => home_url( '/#business' ) ),
		);
	}
	if ( ! defined( 'RANK_MATH_VERSION' ) && ( is_front_page() || is_page() ) ) {
		$graph[] = sgd_org_schema();
	}

	if ( is_singular( 'dich_vu' ) ) {
		$group   = sgd_first_group();
		$service = array(
			'@type'       => 'Service',
			'@id'         => get_permalink() . '#dich-vu',
			'name'        => get_the_title(),
			'url'         => get_permalink(),
			'description' => wp_strip_all_tags( has_excerpt() ? get_the_excerpt() : sgd_meta( 'subtitle' ) ),
			'provider'    => array(
				'@type' => 'ProfessionalService',
				'@id'   => home_url( '/#business' ),
				'name'  => sgd_opt( 'company_full' ),
				'url'   => home_url( '/' ),
			),
			'areaServed'  => array( '@type' => 'Country', 'name' => 'Việt Nam' ),
		);
		if ( $group ) {
			$service['serviceType'] = $group->name;
		}
		if ( sgd_opt( 'expert' ) ) {
			$graph[] = array(
				'@type'        => 'WebPage',
				'@id'          => get_permalink() . '#webpage',
				'url'          => get_permalink(),
				'name'         => get_the_title(),
				'inLanguage'   => 'vi',
				'dateModified' => get_the_modified_date( 'c' ),
				'reviewedBy'   => array( '@type' => 'Person', 'name' => sgd_opt( 'expert' ), 'jobTitle' => sgd_opt( 'expert_title' ) ),
				'mainEntity'   => array( '@id' => get_permalink() . '#dich-vu' ),
			);
		}
		if ( has_post_thumbnail() ) {
			$service['image'] = get_the_post_thumbnail_url( get_the_ID(), 'full' );
		}
		$offers = array();
		foreach ( sgd_packages() as $p ) {
			$n = sgd_price_number( $p['price'] );
			if ( $n ) {
				$offers[] = array(
					'@type'         => 'Offer',
					'name'          => $p['name'],
					'price'         => $n,
					'priceCurrency' => 'VND',
					'description'   => implode( ', ', $p['items'] ),
				);
			}
		}
		if ( ! $offers && sgd_price_number( sgd_meta( 'price' ) ) ) {
			$offers[] = array(
				'@type'         => 'Offer',
				'price'         => sgd_price_number( sgd_meta( 'price' ) ),
				'priceCurrency' => 'VND',
			);
		}
		if ( $offers ) {
			$service['offers'] = $offers;
		}
		$graph[] = $service;

		$q = array();
		foreach ( sgd_lines( sgd_meta( 'faq' ) ) as $f ) {
			if ( $f[0] && $f[1] ) {
				$q[] = array(
					'@type'          => 'Question',
					'name'           => $f[0],
					'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ),
				);
			}
		}
		if ( $q ) {
			$graph[] = array( '@type' => 'FAQPage', 'mainEntity' => $q );
		}
	}

	if ( sgd_is_listing() ) {
		global $wp_query;
		$items = array();
		foreach ( $wp_query->posts as $i => $p ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'url'      => get_permalink( $p ),
				'name'     => get_the_title( $p ),
			);
		}
		if ( $items ) {
			$graph[] = array(
				'@type'      => 'CollectionPage',
				'name'       => wp_strip_all_tags( sgd_listing_h1() ),
				'url'        => is_tax() ? get_term_link( get_queried_object() ) : get_post_type_archive_link( 'dich_vu' ),
				'mainEntity' => array(
					'@type'           => 'ItemList',
					'numberOfItems'   => count( $items ),
					'itemListElement' => $items,
				),
			);
		}
	}

	if ( is_singular( 'post' ) && ! sgd_has_seo_plugin() ) {
		$article = array(
			'@type'            => 'BlogPosting',
			'@id'              => get_permalink() . '#article',
			'headline'         => get_the_title(),
			'url'              => get_permalink(),
			'mainEntityOfPage' => get_permalink(),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'inLanguage'       => 'vi',
			'author'           => sgd_opt( 'expert' ) ? array( '@type' => 'Person', 'name' => sgd_opt( 'expert' ), 'jobTitle' => sgd_opt( 'expert_title' ) ) : array( '@type' => 'Organization', 'name' => sgd_opt( 'company' ), 'url' => home_url( '/' ) ),
			'publisher'        => array( '@id' => home_url( '/#business' ), '@type' => 'Organization', 'name' => sgd_opt( 'company' ), 'logo' => array( '@type' => 'ImageObject', 'url' => get_theme_mod( 'site_logo' ) && ! is_numeric( get_theme_mod( 'site_logo' ) ) ? get_theme_mod( 'site_logo' ) : wp_get_attachment_image_url( (int) get_theme_mod( 'site_logo' ), 'full' ) ) ),
			'description'      => sgd_clip( has_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_post_field( 'post_content', get_the_ID() ) ), 160 ),
		);
		if ( has_post_thumbnail() ) {
			$article['image'] = get_the_post_thumbnail_url( get_the_ID(), 'full' );
		}
		$graph[] = $article;
	}

	if ( ( is_singular( array( 'dich_vu', 'post' ) ) || sgd_is_listing() || is_home() || is_category() || is_tag() || ( is_page() && ! is_front_page() && ! sgd_has_seo_plugin() ) ) && ! sgd_rank_math_breadcrumbs() && ! ( is_singular( 'post' ) && sgd_has_seo_plugin() ) ) {
		$list = array();
		foreach ( sgd_breadcrumb_trail() as $i => $t ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $t[0],
				'item'     => $t[1],
			);
		}
		$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $list );
	}

	if ( $graph ) {
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
	}
}
add_action( 'wp_head', 'sgd_schema', 20 );
