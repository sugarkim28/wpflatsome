<?php
/**
 * SEO cho trang landing: tiêu đề, mô tả, Open Graph (chia sẻ Facebook/Zalo) và dữ liệu cấu trúc.
 *
 * Nếu đã cài Rank Math / Yoast / SEOPress / AIOSEO thì theme KHÔNG in tiêu đề, mô tả, Open Graph
 * (để plugin quản lý, tránh trùng lặp) – chỉ in dữ liệu cấu trúc dự án.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL chuẩn của trang landing (trang chủ dùng địa chỉ trang chủ).
 *
 * @return string
 */
function bds_page_url() {
	return is_front_page() ? home_url( '/' ) : get_permalink();
}

/**
 * Có plugin SEO đang chạy không.
 *
 * @return bool
 */
function bds_has_seo_plugin() {
	return defined( 'RANK_MATH_VERSION' )
		|| defined( 'WPSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| class_exists( 'All_in_One_SEO_Pack' );
}

/**
 * Tiêu đề trang (thẻ <title>).
 *
 * @param array $parts Các phần tiêu đề.
 * @return array
 */
function bds_document_title( $parts ) {
	if ( bds_is_landing_page() && ! bds_has_seo_plugin() && bds_opt( 'seo_title' ) ) {
		return array( 'title' => bds_opt( 'seo_title' ) );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'bds_document_title', 20 );

/**
 * Meta description + Open Graph + Twitter card.
 */
function bds_meta_tags() {
	if ( ! bds_is_landing_page() || bds_has_seo_plugin() ) {
		return;
	}

	$title = bds_opt( 'seo_title' ) ? bds_opt( 'seo_title' ) : wp_get_document_title();
	$desc  = bds_opt( 'seo_description' );
	$url   = bds_page_url();
	$image = bds_img_url( bds_opt( 'seo_image' ), 'full' );
	if ( ! $image ) {
		$image = bds_img_url( bds_opt( 'hero_image' ), 'full' );
	}

	if ( $desc ) {
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
	}
	printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $url ) );
	printf( '<meta property="og:locale" content="vi_VN" />' . "\n" );
	printf( '<meta property="og:type" content="website" />' . "\n" );
	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $desc ) );
	}
	printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $url ) );
	printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	if ( $image ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
	}
	printf( '<meta name="twitter:card" content="%s" />' . "\n", $image ? 'summary_large_image' : 'summary' );
}
add_action( 'wp_head', 'bds_meta_tags', 1 );

/**
 * WordPress tự in <link rel="canonical"> cho trang; bỏ đi khi theme đã in để không bị trùng.
 */
function bds_remove_core_canonical() {
	if ( bds_is_landing_page() && ! bds_has_seo_plugin() ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
}
add_action( 'wp', 'bds_remove_core_canonical' );

/**
 * Dữ liệu cấu trúc (schema.org JSON-LD): dự án căn hộ + đơn vị phân phối.
 */
function bds_structured_data() {
	if ( ! bds_is_landing_page() || ! bds_opt( 'schema_enable' ) ) {
		return;
	}

	$url     = bds_page_url();
	$phone   = bds_opt( 'hotline' );
	$address = array_filter(
		array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => bds_opt( 'schema_street' ),
			'addressLocality' => bds_opt( 'schema_locality' ),
			'addressRegion'   => bds_opt( 'schema_region' ),
			'addressCountry'  => 'VN',
		)
	);

	$project = array(
		'@type'       => 'ApartmentComplex',
		'@id'         => $url . '#du-an',
		'name'        => bds_opt( 'hero_title' ),
		'description' => bds_opt( 'seo_description' ),
		'url'         => $url,
		'address'     => $address,
	);
	if ( bds_opt( 'schema_alt_name' ) ) {
		$project['alternateName'] = bds_opt( 'schema_alt_name' );
	}
	if ( absint( bds_opt( 'schema_units' ) ) ) {
		$project['numberOfAccommodationUnits'] = absint( bds_opt( 'schema_units' ) );
	}
	$image = bds_img_url( bds_opt( 'hero_image' ), 'full' );
	if ( $image ) {
		$project['image'] = $image;
	}
	if ( $phone ) {
		$project['telephone'] = $phone;
	}

	$graph = array( $project );

	if ( bds_opt( 'schema_agency_name' ) ) {
		$agency = array(
			'@type' => 'RealEstateAgent',
			'@id'   => $url . '#dai-ly',
			'name'  => bds_opt( 'schema_agency_name' ),
			'url'   => $url,
		);
		if ( bds_opt( 'schema_agency_address' ) ) {
			$agency['address'] = array(
				'@type'          => 'PostalAddress',
				'streetAddress'  => bds_opt( 'schema_agency_address' ),
				'addressCountry' => 'VN',
			);
		}
		if ( $phone ) {
			$agency['telephone'] = $phone;
		}
		if ( is_email( bds_opt( 'contact_email' ) ) ) {
			$agency['email'] = bds_opt( 'contact_email' );
		}
		$graph[] = $agency;
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => array_map( 'array_filter', $graph ),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n";
}
add_action( 'wp_head', 'bds_structured_data', 20 );
