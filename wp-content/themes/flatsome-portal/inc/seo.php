<?php
/**
 * SEO: breadcrumb, dữ liệu cấu trúc (schema), noindex trang lọc, mô tả dự phòng.
 * Hoạt động cùng Rank Math: Rank Math lo tiêu đề / mô tả / sitemap / Organization / Breadcrumb
 * (nếu bật), theme bổ sung schema dự án, danh sách và FAQ.
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Có plugin SEO lo phần meta không.
 *
 * @return bool
 */
function sgp_has_seo_plugin() {
	return defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Rank Math đang bật breadcrumb không.
 *
 * @return bool
 */
function sgp_rank_math_breadcrumbs() {
	return function_exists( 'rank_math_the_breadcrumbs' ) && class_exists( '\RankMath\Helper' ) && \RankMath\Helper::get_settings( 'general.breadcrumbs' );
}

/**
 * Chuỗi breadcrumb cho trang hiện tại: [[tên, url], ...].
 *
 * @return array
 */
function sgp_breadcrumb_trail() {
	$trail   = array( array( 'Trang chủ', home_url( '/' ) ) );
	$archive = array( 'Dự án', get_post_type_archive_link( 'du_an' ) );
	if ( is_singular( 'du_an' ) ) {
		$trail[] = $archive;
		$area    = sgp_first_term( 'khu_vuc' );
		if ( $area ) {
			$trail[] = array( $area->name, get_term_link( $area ) );
		}
		$trail[] = array( get_the_title(), get_permalink() );
	} elseif ( is_post_type_archive( 'du_an' ) ) {
		$trail[] = $archive;
	} elseif ( is_tax() ) {
		$term = get_queried_object();
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
function sgp_breadcrumbs() {
	if ( sgp_rank_math_breadcrumbs() ) {
		echo '<div class="sgp-breadcrumb">';
		rank_math_the_breadcrumbs();
		echo '</div>';
		return;
	}
	$trail = sgp_breadcrumb_trail();
	$last  = count( $trail ) - 1;
	echo '<nav class="sgp-breadcrumb" aria-label="Breadcrumb"><ol>';
	foreach ( $trail as $i => $t ) {
		echo '<li>' . ( $i < $last ? '<a href="' . esc_url( $t[1] ) . '">' . esc_html( $t[0] ) . '</a>' : '<span aria-current="page">' . esc_html( $t[0] ) . '</span>' ) . '</li>';
	}
	echo '</ol></nav>';
}

/**
 * Noindex trang danh sách khi đang lọc (tránh hàng trăm trang trùng lặp).
 *
 * @param array $robots Robots.
 * @return array
 */
function sgp_robots( $robots ) {
	if ( ( sgp_is_listing() && sgp_active_filters() ) || isset( $_GET['sgp_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'sgp_robots' );

/**
 * Như trên cho Rank Math.
 *
 * @param array $robots Robots.
 * @return array
 */
function sgp_rank_math_robots( $robots ) {
	if ( ( sgp_is_listing() && sgp_active_filters() ) || isset( $_GET['sgp_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'sgp_rank_math_robots' );

/**
 * Bỏ UX Blocks của Flatsome khỏi sitemap Rank Math.
 *
 * @param bool   $exclude Loại bỏ?
 * @param string $type    Loại bài.
 * @return bool
 */
function sgp_sitemap_exclude( $exclude, $type ) {
	return 'blocks' === $type ? true : $exclude;
}
add_filter( 'rank_math/sitemap/exclude_post_type', 'sgp_sitemap_exclude', 10, 2 );

/**
 * Mô tả dự phòng khi không có plugin SEO.
 */
function sgp_fallback_meta() {
	if ( sgp_has_seo_plugin() ) {
		return;
	}
	$desc = '';
	if ( is_singular( 'du_an' ) ) {
		$desc = has_excerpt() ? get_the_excerpt() : sgp_meta( 'subtitle' );
	} elseif ( is_tax() ) {
		$desc = term_description();
	} elseif ( is_post_type_archive( 'du_an' ) ) {
		$desc = sgp_opt( 'archive_intro' );
	}
	$desc = trim( wp_strip_all_tags( (string) $desc ) );
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( wp_trim_words( $desc, 30, '…' ) ) . "\">\n";
	}
}
add_action( 'wp_head', 'sgp_fallback_meta', 1 );

/**
 * JSON-LD.
 */
function sgp_schema() {
	$graph = array();
	$org   = array(
		'@type'     => 'RealEstateAgent',
		'@id'       => home_url( '/#agent' ),
		'name'      => sgp_opt( 'company_full' ),
		'url'       => home_url( '/' ),
		'telephone' => sgp_opt( 'hotline' ),
		'email'     => sgp_opt( 'email' ),
		'address'   => array(
			'@type'          => 'PostalAddress',
			'streetAddress'  => sgp_opt( 'address' ),
			'addressCountry' => 'VN',
		),
	);
	$logo = get_theme_mod( 'site_logo' );
	if ( $logo ) {
		$org['logo'] = is_numeric( $logo ) ? wp_get_attachment_image_url( $logo, 'full' ) : $logo;
	}
	if ( ! defined( 'RANK_MATH_VERSION' ) && ( is_front_page() || is_page() ) ) {
		$graph[] = $org;
	}

	if ( is_singular( 'du_an' ) ) {
		$id    = get_the_ID();
		$type  = sgp_first_term( 'loai_hinh' );
		$area  = sgp_first_term( 'khu_vuc' );
		$dev   = sgp_first_term( 'chu_dau_tu' );
		$place = array(
			'@type'       => ( $type && false !== strpos( $type->slug, 'can-ho' ) ) ? 'ApartmentComplex' : 'Residence',
			'@id'         => get_permalink() . '#du-an',
			'name'        => get_the_title(),
			'url'         => get_permalink(),
			'description' => wp_strip_all_tags( has_excerpt() ? get_the_excerpt() : sgp_meta( 'subtitle' ) ),
		);
		if ( has_post_thumbnail() ) {
			$place['image'] = get_the_post_thumbnail_url( $id, 'full' );
		}
		if ( sgp_meta( 'address' ) ) {
			$place['address'] = array_filter(
				array(
					'@type'          => 'PostalAddress',
					'streetAddress'  => sgp_meta( 'address' ),
					'addressRegion'  => $area ? $area->name : '',
					'addressCountry' => 'VN',
				)
			);
		}
		if ( $dev ) {
			$place['additionalProperty'] = array(
				array(
					'@type' => 'PropertyValue',
					'name'  => 'Chủ đầu tư',
					'value' => $dev->name,
				),
			);
		}
		$graph[] = $place;

		$faq = sgp_lines( sgp_meta( 'faq' ) );
		if ( $faq ) {
			$q = array();
			foreach ( $faq as $f ) {
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
	}

	if ( sgp_is_listing() && ! sgp_active_filters() ) {
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
				'name'       => wp_strip_all_tags( sgp_listing_h1() ),
				'url'        => is_tax() ? get_term_link( get_queried_object() ) : get_post_type_archive_link( 'du_an' ),
				'mainEntity' => array(
					'@type'           => 'ItemList',
					'numberOfItems'   => count( $items ),
					'itemListElement' => $items,
				),
			);
		}
	}

	if ( ( is_singular( 'du_an' ) || sgp_is_listing() ) && ! sgp_rank_math_breadcrumbs() ) {
		$list = array();
		foreach ( sgp_breadcrumb_trail() as $i => $t ) {
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
add_action( 'wp_head', 'sgp_schema', 20 );

/**
 * Tiêu đề H1 của trang danh sách.
 *
 * @return string
 */
function sgp_listing_h1() {
	if ( is_tax() ) {
		$term = get_queried_object();
		$h1   = get_term_meta( $term->term_id, '_sgp_h1', true );
		if ( $h1 ) {
			return $h1;
		}
		$prefix = array(
			'khu_vuc'    => 'Dự án bất động sản tại ',
			'chu_dau_tu' => 'Dự án của ',
			'loai_hinh'  => 'Dự án ',
		);
		return ( isset( $prefix[ $term->taxonomy ] ) ? $prefix[ $term->taxonomy ] : '' ) . ( 'loai_hinh' === $term->taxonomy ? mb_strtolower( $term->name ) : $term->name );
	}
	return sgp_opt( 'archive_title' );
}
