<?php
/**
 * Bộ lọc dự án (GET). Trên trang khu vực / chủ đầu tư / loại hình thì giữ cố định mục đó.
 * $args['action'] = URL gửi tới (mặc định: trang hiện tại), $args['compact'] = gọn cho trang chủ.
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

$sgp_fixed  = is_tax() ? get_queried_object()->taxonomy : '';
$sgp_active = sgp_active_filters();
$sgp_action = ! empty( $args['action'] ) ? $args['action'] : ( is_tax() ? get_term_link( get_queried_object() ) : get_post_type_archive_link( 'du_an' ) );
$sgp_labels = array(
	'khu_vuc'    => 'Khu vực',
	'loai_hinh'  => 'Loại hình',
	'khoang_gia' => 'Khoảng giá',
	'trang_thai' => 'Trạng thái',
	'chu_dau_tu' => 'Chủ đầu tư',
);
?>
<form class="sgp-filter<?php echo ! empty( $args['compact'] ) ? ' sgp-filter--compact' : ''; ?>" method="get" action="<?php echo esc_url( $sgp_action ); ?>" role="search" aria-label="Lọc dự án">
	<?php foreach ( $sgp_labels as $sgp_tax => $sgp_label ) : ?>
		<?php
		if ( $sgp_tax === $sgp_fixed ) {
			continue;
		}
		$sgp_terms = get_terms( array( 'taxonomy' => $sgp_tax, 'hide_empty' => true ) );
		if ( ! $sgp_terms || is_wp_error( $sgp_terms ) ) {
			continue;
		}
		$sgp_param = sgp_filter_params()[ $sgp_tax ];
		?>
		<label class="screen-reader-text" for="sgp-f-<?php echo esc_attr( $sgp_param ); ?>"><?php echo esc_html( $sgp_label ); ?></label>
		<select id="sgp-f-<?php echo esc_attr( $sgp_param ); ?>" name="<?php echo esc_attr( $sgp_param ); ?>">
			<option value=""><?php echo esc_html( $sgp_label ); ?></option>
			<?php foreach ( $sgp_terms as $sgp_t ) : ?>
				<option value="<?php echo esc_attr( $sgp_t->slug ); ?>" <?php selected( isset( $sgp_active[ $sgp_tax ] ) ? $sgp_active[ $sgp_tax ] : '', $sgp_t->slug ); ?>><?php echo esc_html( $sgp_t->name ); ?></option>
			<?php endforeach; ?>
		</select>
	<?php endforeach; ?>
	<button type="submit" class="button sgp-btn">Tìm dự án</button>
	<?php if ( $sgp_active && empty( $args['compact'] ) ) : ?>
		<a class="sgp-filter__reset" href="<?php echo esc_url( $sgp_action ); ?>">Xoá lọc</a>
	<?php endif; ?>
</form>
