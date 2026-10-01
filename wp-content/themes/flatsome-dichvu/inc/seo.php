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
	} elseif ( is_tax( 'nhom_dich_vu' ) ) {
		$term    = get_queried_object();
		$trail[] = $archive;
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $anc ) {
			$a       = get_term( $anc, $term->taxonomy );
			$trail[] = array( $a->name, get_term_link( $a ) );
		}
		$trail[] = array( $term->name, get_term_link( $term ) );
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
	if ( isset( $_GET['sgd_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
	if ( isset( $_GET['sgd_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
	} elseif ( is_front_page() ) {
		$desc = sgd_opt( 'company' ) . ' – ' . sgd_opt( 'tagline' ) . '. ' . sgd_opt( 'archive_intro' );
	}
	$desc = trim( wp_strip_all_tags( (string) $desc ) );
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( wp_trim_words( $desc, 32, '…' ) ) . "\">\n";
	}
}
add_action( 'wp_head', 'sgd_fallback_meta', 1 );

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
	return $org;
}

/**
 * JSON-LD.
 */
function sgd_schema() {
	$graph = array();
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

	if ( ( is_singular( 'dich_vu' ) || sgd_is_listing() ) && ! sgd_rank_math_breadcrumbs() ) {
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
