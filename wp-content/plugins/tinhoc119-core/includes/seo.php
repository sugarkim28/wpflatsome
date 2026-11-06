<?php
/**
 * SEO kỹ thuật: meta title/description, canonical, Open Graph, robots, sitemap, breadcrumb,
 * dữ liệu có cấu trúc (Organization, LocalBusiness, WebSite, BreadcrumbList, TechArticle, Service, FAQPage),
 * alt ảnh, WebP. Tự nhường cho Rank Math / Yoast / AIOSEO / SEOPress nếu đang dùng.
 *
 * @package Tinhoc119_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin SEO đang dùng ('' nếu không có).
 *
 * @return string
 */
function th119_seo_plugin() {
	if ( defined( 'RANK_MATH_VERSION' ) ) {
		return 'Rank Math';
	}
	if ( defined( 'WPSEO_VERSION' ) ) {
		return 'Yoast SEO';
	}
	if ( defined( 'AIOSEO_VERSION' ) ) {
		return 'All in One SEO';
	}
	if ( defined( 'SEOPRESS_VERSION' ) ) {
		return 'SEOPress';
	}
	return '';
}

/**
 * Plugin này có xuất meta không.
 *
 * @return bool
 */
function th119_seo_active() {
	return th119_opt( 'seo_enabled' ) && '' === th119_seo_plugin();
}

/**
 * Cắt chuỗi theo từ.
 *
 * @param string $text Chuỗi.
 * @param int    $max  Độ dài tối đa.
 * @return string
 */
function th119_clip( $text, $max = 155 ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max - 1 );
	$sp  = mb_strrpos( $cut, ' ' );
	return rtrim( $sp > $max * 0.6 ? mb_substr( $cut, 0, $sp ) : $cut, ' ,.;:–-' ) . '…';
}

/**
 * Mô tả trang hiện tại.
 *
 * @return string
 */
function th119_meta_description() {
	if ( is_front_page() ) {
		$d = th119_opt( 'home_desc' );
		if ( ! $d ) {
			$d = sprintf( '%s – sửa laptop, máy tính, máy in, mạng tại %s, %s. Hỗ trợ IT doanh nghiệp, kiểm tra tận nơi. Hotline %s.', th119_opt( 'brand' ), th119_opt( 'district' ), th119_opt( 'city' ), th119_opt( 'hotline' ) );
		}
		return th119_clip( $d );
	}
	if ( is_singular() ) {
		$id = get_queried_object_id();
		$d  = get_post_meta( $id, '_th_seo_desc', true );
		if ( ! $d ) {
			$d = get_post_meta( $id, '_th_summary', true );
		}
		if ( ! $d ) {
			$p = get_post( $id );
			$d = $p->post_excerpt ? $p->post_excerpt : $p->post_content;
		}
		return th119_clip( $d );
	}
	if ( is_category() || is_tax() || is_tag() ) {
		$t = get_queried_object();
		$d = get_term_meta( $t->term_id, 'th_seo_desc', true );
		return th119_clip( $d ? $d : ( $t->description ? $t->description : sprintf( 'Hướng dẫn, kiến thức về %s từ kỹ thuật viên %s.', $t->name, th119_opt( 'brand' ) ) ) );
	}
	if ( is_post_type_archive( 'dich_vu' ) ) {
		return th119_clip( sprintf( 'Dịch vụ sửa laptop, máy tính, nâng cấp, bảo trì và IT doanh nghiệp tại %s, %s. Kiểm tra, báo giá trước khi sửa. Hotline %s.', th119_opt( 'district' ), th119_opt( 'city' ), th119_opt( 'hotline' ) ) );
	}
	if ( is_post_type_archive( 'th_project' ) ) {
		return th119_clip( 'Các dự án IT, bảo trì hệ thống, lắp đặt mạng văn phòng ' . th119_opt( 'brand' ) . ' đã thực hiện.' );
	}
	return '';
}

/**
 * Tiêu đề trang (khi không dùng plugin SEO).
 *
 * @param array $parts Thành phần tiêu đề.
 * @return array
 */
function th119_title_parts( $parts ) {
	if ( ! th119_seo_active() ) {
		return $parts;
	}
	if ( is_front_page() && th119_opt( 'home_title' ) ) {
		return array( 'title' => th119_opt( 'home_title' ) );
	}
	if ( is_singular() ) {
		$t = get_post_meta( get_queried_object_id(), '_th_seo_title', true );
		if ( $t ) {
			return array( 'title' => $t );
		}
	}
	if ( is_category() ) {
		$t = get_term_meta( get_queried_object_id(), 'th_seo_title', true );
		if ( $t ) {
			return array( 'title' => $t );
		}
		$parts['title'] = single_cat_title( '', false ) . ' – Hướng dẫn sửa lỗi';
	}
	if ( is_post_type_archive( 'dich_vu' ) ) {
		$parts['title'] = 'Dịch vụ sửa máy tính, laptop, IT ' . th119_opt( 'district' );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'th119_title_parts', 20 );

/**
 * Dấu ngăn tiêu đề.
 *
 * @return string
 */
function th119_title_sep() {
	return '|';
}
add_filter( 'document_title_separator', 'th119_title_sep' );

/**
 * URL chuẩn (canonical) cho trang không phải bài viết.
 *
 * @return string
 */
function th119_canonical_url() {
	if ( is_singular() ) {
		return wp_get_canonical_url();
	}
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$url   = '';
	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_home() ) {
		$url = get_permalink( (int) get_option( 'page_for_posts' ) );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_post_type_archive() ) {
		$pt  = (array) get_query_var( 'post_type' );
		$url = get_post_type_archive_link( $pt ? (string) reset( $pt ) : 'post' );
	}
	if ( ! $url || is_wp_error( $url ) ) {
		return '';
	}
	return $paged > 1 ? get_pagenum_link( $paged ) : $url;
}

/**
 * Meta description, canonical, Open Graph, Twitter.
 */
function th119_head_meta() {
	if ( ! th119_seo_active() || is_404() ) {
		return;
	}
	$desc = th119_meta_description();
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( ! is_singular() && ! is_search() ) {
		$c = th119_canonical_url();
		if ( $c ) {
			echo '<link rel="canonical" href="' . esc_url( $c ) . '">' . "\n";
		}
	}
	$title = wp_get_document_title();
	$url   = is_singular() ? wp_get_canonical_url() : th119_canonical_url();
	$img   = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$img = get_the_post_thumbnail_url( null, 'large' );
	}
	if ( ! $img && th119_opt( 'og_image_id' ) ) {
		$img = wp_get_attachment_image_url( (int) th119_opt( 'og_image_id' ), 'full' );
	}
	if ( ! $img && th119_opt( 'logo_id' ) ) {
		$img = wp_get_attachment_image_url( (int) th119_opt( 'logo_id' ), 'full' );
	}
	$tags = array(
		'og:locale'      => 'vi_VN',
		'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
		'og:site_name'   => th119_opt( 'brand' ),
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => $url ? $url : '',
		'og:image'       => $img,
	);
	foreach ( $tags as $k => $v ) {
		if ( $v ) {
			echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">' . "\n";
		}
	}
	if ( is_singular( 'post' ) ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ( $img ? 'summary_large_image' : 'summary' ) . '">' . "\n";
}
add_action( 'wp_head', 'th119_head_meta', 2 );

/**
 * Robots: không lập chỉ mục trang tìm kiếm, chuyên mục trống, trang tra cứu/gửi yêu cầu có tham số.
 *
 * @param array $robots Chỉ thị.
 * @return array
 */
function th119_robots( $robots ) {
	if ( '' !== th119_seo_plugin() ) {
		return $robots;
	}
	if ( is_search() || ( is_category() && 0 === (int) get_queried_object()->count && ! get_term_meta( get_queried_object_id(), 'th_intro', true ) ) || isset( $_GET['th_ok'] ) || isset( $_GET['th_err'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	if ( ! isset( $robots['noindex'] ) ) {
		$robots['max-image-preview'] = 'large';
	}
	return $robots;
}
add_filter( 'wp_robots', 'th119_robots' );

/**
 * robots.txt: chặn thư mục ảnh riêng tư và trang kết quả tìm kiếm nội bộ.
 *
 * @param string $output Nội dung.
 * @param bool   $public Website công khai.
 * @return string
 */
function th119_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return $output;
	}
	$extra = "Disallow: /wp-content/uploads/th119-private/\nDisallow: /?s=\nDisallow: /search/\n";
	return preg_replace( '/(Disallow: \/wp-admin\/\n)/', "$1$extra", $output, 1 );
}
add_filter( 'robots_txt', 'th119_robots_txt', 20, 2 );

/**
 * Sitemap lõi WordPress: bỏ danh sách tác giả (lộ tên đăng nhập), bỏ loại nội dung riêng tư.
 *
 * @param WP_Sitemaps_Provider $provider Provider.
 * @param string               $name     Tên.
 * @return WP_Sitemaps_Provider|false
 */
function th119_sitemap_providers( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'th119_sitemap_providers', 10, 2 );

/* ---------------------------------------------------------------------------
 * Breadcrumb.
 * ------------------------------------------------------------------------- */

/**
 * Các cấp breadcrumb: [ [tên, url], … ] (mục cuối không có url).
 *
 * @return array
 */
function th119_breadcrumb_items() {
	$items = array( array( 'Trang chủ', home_url( '/' ) ) );
	$kb    = get_page_by_path( 'kien-thuc' );
	if ( is_singular( 'post' ) ) {
		if ( $kb ) {
			$items[] = array( get_the_title( $kb ), get_permalink( $kb ) );
		}
		$cats = get_the_category();
		if ( $cats ) {
			// Chọn chuyên mục sâu nhất.
			usort( $cats, function ( $a, $b ) { return count( get_ancestors( $b->term_id, 'category' ) ) - count( get_ancestors( $a->term_id, 'category' ) ); } ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment
			foreach ( array_reverse( get_ancestors( $cats[0]->term_id, 'category' ) ) as $aid ) {
				$items[] = array( get_cat_name( $aid ), get_category_link( $aid ) );
			}
			$items[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
		}
		$items[] = array( get_the_title(), '' );
	} elseif ( is_singular( 'dich_vu' ) ) {
		$items[] = array( 'Dịch vụ', get_post_type_archive_link( 'dich_vu' ) );
		$items[] = array( get_the_title(), '' );
	} elseif ( is_singular( 'th_project' ) ) {
		$items[] = array( 'Dự án', get_post_type_archive_link( 'th_project' ) );
		$items[] = array( get_the_title(), '' );
	} elseif ( is_page() && ! is_front_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $aid ) {
			$items[] = array( get_the_title( $aid ), get_permalink( $aid ) );
		}
		$items[] = array( get_the_title(), '' );
	} elseif ( is_category() ) {
		if ( $kb ) {
			$items[] = array( get_the_title( $kb ), get_permalink( $kb ) );
		}
		foreach ( array_reverse( get_ancestors( get_queried_object_id(), 'category' ) ) as $aid ) {
			$items[] = array( get_cat_name( $aid ), get_category_link( $aid ) );
		}
		$items[] = array( single_cat_title( '', false ), '' );
	} elseif ( is_tax( 'nhom_dich_vu' ) ) {
		$items[] = array( 'Dịch vụ', get_post_type_archive_link( 'dich_vu' ) );
		$items[] = array( single_term_title( '', false ), '' );
	} elseif ( is_post_type_archive( 'dich_vu' ) ) {
		$items[] = array( 'Dịch vụ', '' );
	} elseif ( is_post_type_archive( 'th_project' ) ) {
		$items[] = array( 'Dự án', '' );
	} elseif ( is_home() ) {
		$items[] = array( get_the_title( (int) get_option( 'page_for_posts' ) ), '' );
	} elseif ( is_search() ) {
		$items[] = array( 'Tìm kiếm: ' . get_search_query(), '' );
	} elseif ( is_tag() ) {
		$items[] = array( single_tag_title( '', false ), '' );
	} elseif ( is_404() ) {
		$items[] = array( 'Không tìm thấy trang', '' );
	}
	return apply_filters( 'th119_breadcrumb_items', $items );
}

/**
 * HTML breadcrumb.
 *
 * @return string
 */
function th119_breadcrumb_html() {
	$items = th119_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return '';
	}
	$html = '<nav class="th-breadcrumb" aria-label="Breadcrumb"><ol>';
	foreach ( $items as $it ) {
		$html .= $it[1] ? '<li><a href="' . esc_url( $it[1] ) . '">' . esc_html( $it[0] ) . '</a></li>' : '<li aria-current="page">' . esc_html( $it[0] ) . '</li>';
	}
	return $html . '</ol></nav>';
}
add_shortcode( 'th119_breadcrumb', 'th119_breadcrumb_html' );

/* ---------------------------------------------------------------------------
 * Dữ liệu có cấu trúc.
 * ------------------------------------------------------------------------- */

/**
 * Node LocalBusiness (đồng thời là Organization của website).
 *
 * @return array
 */
function th119_schema_business() {
	$id   = home_url( '/#localbusiness' );
	$node = array(
		'@type'     => 'LocalBusiness',
		'@id'       => $id,
		'name'      => th119_opt( 'brand' ),
		'url'       => home_url( '/' ),
		'telephone' => th119_opt( 'hotline' ),
		'slogan'    => th119_opt( 'slogan' ),
		'address'   => array_filter(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => trim( th119_opt( 'street' ) . ( th119_opt( 'ward' ) ? ', ' . th119_opt( 'ward' ) : '' ) ),
				'addressLocality' => th119_opt( 'district' ),
				'addressRegion'   => th119_opt( 'city' ),
				'postalCode'      => th119_opt( 'postal_code' ),
				'addressCountry'  => 'VN',
			)
		),
		'hasMap'    => th119_maps_url(),
	);
	if ( th119_opt( 'legal_name' ) ) {
		$node['legalName'] = th119_opt( 'legal_name' );
	}
	if ( th119_opt( 'email' ) ) {
		$node['email'] = th119_opt( 'email' );
	}
	if ( th119_opt( 'founded' ) ) {
		$node['foundingDate'] = th119_opt( 'founded' );
	}
	$logo = th119_opt( 'logo_id' ) ? wp_get_attachment_image_url( (int) th119_opt( 'logo_id' ), 'full' ) : '';
	if ( $logo ) {
		$node['logo']  = $logo;
		$node['image'] = $logo;
	}
	if ( is_numeric( th119_opt( 'lat' ) ) && is_numeric( th119_opt( 'lng' ) ) ) {
		$node['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) th119_opt( 'lat' ),
			'longitude' => (float) th119_opt( 'lng' ),
		);
	}
	$days = array(
		'Mo' => 'Monday',
		'Tu' => 'Tuesday',
		'We' => 'Wednesday',
		'Th' => 'Thursday',
		'Fr' => 'Friday',
		'Sa' => 'Saturday',
		'Su' => 'Sunday',
	);
	$spec = array();
	foreach ( th119_pipe_rows( th119_opt( 'hours_schema' ), 3 ) as $row ) {
		$spec[] = array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => array_values( array_intersect_key( $days, array_flip( explode( ',', $row[0] ) ) ) ),
			'opens'     => $row[1],
			'closes'    => $row[2],
		);
	}
	if ( $spec ) {
		$node['openingHoursSpecification'] = $spec;
	}
	$areas = array_filter( array_map( 'trim', explode( ',', (string) th119_opt( 'area_served' ) ) ) );
	if ( $areas ) {
		$node['areaServed'] = array_map(
			function ( $a ) {
				return array(
					'@type' => 'Place',
					'name'  => $a,
				);
			},
			$areas
		);
	}
	$same = array_values( array_filter( array( th119_opt( 'facebook' ), th119_opt( 'youtube' ), th119_opt( 'maps_url' ) ) ) );
	if ( $same ) {
		$node['sameAs'] = $same;
	}
	return $node;
}

/**
 * Theme có hiển thị FAQ trên trang không (chỉ xuất FAQPage khi FAQ hiển thị cho người đọc).
 *
 * @return bool
 */
function th119_theme_shows_faq() {
	return current_theme_supports( 'th119-faq' );
}

/**
 * Xuất schema dạng @graph.
 */
function th119_schema_output() {
	if ( ! th119_opt( 'schema_enabled' ) || is_404() || is_search() ) {
		return;
	}
	$seo_plugin = '' !== th119_seo_plugin();
	$old_theme  = function_exists( 'sgd_schema' ); // Theme cũ đã có schema dịch vụ/FAQ riêng.
	$graph      = array( th119_schema_business() );
	$biz        = array( '@id' => home_url( '/#localbusiness' ) );

	if ( ! $seo_plugin ) {
		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => th119_opt( 'brand' ),
			'inLanguage'      => 'vi-VN',
			'publisher'       => $biz,
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);
		$crumbs = th119_breadcrumb_items();
		if ( count( $crumbs ) > 1 ) {
			$list = array();
			foreach ( $crumbs as $i => $c ) {
				$el = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => $c[0],
				);
				if ( $c[1] ) {
					$el['item'] = $c[1];
				}
				$list[] = $el;
			}
			$graph[] = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => ( is_singular() ? get_permalink() : th119_canonical_url() ) . '#breadcrumb',
				'itemListElement' => $list,
			);
		}
	}

	if ( is_singular( 'post' ) && ! $seo_plugin ) {
		$p       = get_queried_object();
		$author  = get_userdata( $p->post_author );
		$article = array(
			'@type'            => 'TechArticle',
			'@id'              => get_permalink( $p ) . '#article',
			'headline'         => th119_clip( get_the_title( $p ), 110 ),
			'description'      => th119_meta_description(),
			'datePublished'    => get_the_date( 'c', $p ),
			'dateModified'     => get_the_modified_date( 'c', $p ),
			'mainEntityOfPage' => get_permalink( $p ),
			'inLanguage'       => 'vi-VN',
			'publisher'        => $biz,
			'author'           => array(
				'@type' => 'Person',
				'name'  => $author ? $author->display_name : th119_opt( 'brand' ),
				'url'   => $author ? get_author_posts_url( $author->ID ) : home_url( '/' ),
			),
		);
		if ( has_post_thumbnail( $p ) ) {
			$article['image'] = get_the_post_thumbnail_url( $p, 'large' );
		}
		$cats = get_the_category( $p->ID );
		if ( $cats ) {
			$article['articleSection'] = $cats[0]->name;
		}
		$graph[] = $article;
	}

	if ( is_singular( 'dich_vu' ) && ! $old_theme ) {
		$id      = get_queried_object_id();
		$service = array(
			'@type'       => 'Service',
			'@id'         => get_permalink( $id ) . '#service',
			'name'        => get_the_title( $id ),
			'description' => th119_meta_description(),
			'url'         => get_permalink( $id ),
			'provider'    => $biz,
			'areaServed'  => th119_opt( 'area_served' ) ? th119_opt( 'area_served' ) : th119_opt( 'city' ),
		);
		$terms   = get_the_terms( $id, 'nhom_dich_vu' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$service['serviceType'] = $terms[0]->name;
		}
		if ( has_post_thumbnail( $id ) ) {
			$service['image'] = get_the_post_thumbnail_url( $id, 'large' );
		}
		$graph[] = $service;
	}

	// FAQPage: chỉ khi theme hiển thị đúng các câu hỏi này trên trang.
	if ( is_singular( array( 'post', 'dich_vu' ) ) && th119_theme_shows_faq() && ! ( $old_theme && is_singular( 'dich_vu' ) ) ) {
		$faq = th119_get_faq( get_queried_object_id() );
		if ( count( $faq ) >= 2 ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => get_permalink() . '#faq',
				'mainEntity' => array_map(
					function ( $f ) {
						return array(
							'@type'          => 'Question',
							'name'           => $f[0],
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => $f[1],
							),
						);
					},
					$faq
				),
			);
		}
	}

	$graph = apply_filters( 'th119_schema_graph', $graph );
	echo '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	) . "</script>\n";
}
add_action( 'wp_head', 'th119_schema_output', 25 );

/**
 * Rank Math: tránh trùng LocalBusiness khi plugin này đã xuất.
 *
 * @param array $data Dữ liệu.
 * @return array
 */
function th119_rank_math_dedupe( $data ) {
	if ( th119_opt( 'schema_enabled' ) && is_array( $data ) ) {
		foreach ( $data as $k => $node ) {
			if ( isset( $node['@type'] ) && in_array( $node['@type'], array( 'LocalBusiness', 'ComputerStore' ), true ) ) {
				unset( $data[ $k ] );
			}
		}
	}
	return $data;
}
add_filter( 'rank_math/json_ld', 'th119_rank_math_dedupe', 99 );

/* ---------------------------------------------------------------------------
 * Ảnh: alt dự phòng, WebP cho ảnh thu nhỏ.
 * ------------------------------------------------------------------------- */

/**
 * Ảnh thiếu alt → dùng tiêu đề ảnh hoặc tiêu đề bài.
 *
 * @param array   $attr       Thuộc tính.
 * @param WP_Post $attachment Ảnh.
 * @return array
 */
function th119_image_alt_fallback( $attr, $attachment ) {
	if ( empty( $attr['alt'] ) ) {
		$alt = trim( (string) get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) );
		if ( '' === $alt ) {
			$alt = $attachment->post_parent ? get_the_title( $attachment->post_parent ) : '';
		}
		if ( '' === $alt ) {
			$alt = preg_replace( '/[-_]+/', ' ', $attachment->post_title );
		}
		$attr['alt'] = $alt;
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'th119_image_alt_fallback', 10, 2 );

/**
 * Ảnh tải lên dạng JPEG/PNG: các kích thước thu nhỏ được lưu thêm dạng WebP (nhẹ hơn 25–35%).
 * Ảnh gốc giữ nguyên. Chỉ bật khi máy chủ hỗ trợ WebP.
 *
 * @param array $formats Định dạng.
 * @return array
 */
function th119_webp_output( $formats ) {
	if ( ! th119_opt( 'webp' ) || ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return $formats;
	}
	$formats['image/jpeg'] = 'image/webp';
	return $formats;
}
add_filter( 'image_editor_output_format', 'th119_webp_output' );
