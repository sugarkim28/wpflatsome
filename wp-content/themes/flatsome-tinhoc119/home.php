<?php
/**
 * Trang danh sách bài viết (khi đặt "Trang bài viết" trong Cài đặt → Đọc).
 *
 * @package Flatsome_Tinhoc119
 */

defined( 'ABSPATH' ) || exit;

get_header();
$th_title = is_home() ? ( get_option( 'page_for_posts' ) ? get_the_title( get_option( 'page_for_posts' ) ) : 'Bài viết' ) : wp_strip_all_tags( get_the_archive_title() );
$th_desc  = is_home() ? '' : get_the_archive_description();
$th_extra = '';
if ( is_author() ) {
	$th_uid   = (int) get_queried_object_id();
	$th_job   = get_user_meta( $th_uid, 'th_job_title', true );
	$th_extra = '<div class="th-author th-author--head"><div class="th-author__avatar">' . get_avatar( $th_uid, 72 ) . '</div><div class="th-author__body">' . ( $th_job ? '<p class="th-author__label">' . esc_html( $th_job ) . '</p>' : '' ) . ( get_the_author_meta( 'description', $th_uid ) ? '<p class="th-author__bio">' . esc_html( get_the_author_meta( 'description', $th_uid ) ) . '</p>' : '' ) . '</div></div>';
	$th_desc  = '';
}
?>
<div id="content" class="th-archive">
	<?php th119t_pagehead( $th_title, $th_desc, $th_extra ); ?>
	<div class="th-wrap">
		<?php if ( have_posts() ) : ?>
			<div class="th-posts">
				<?php
				while ( have_posts() ) :
					the_post();
					echo th119t_card( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				endwhile;
				?>
			</div>
			<?php th119t_pagination(); ?>
		<?php else : ?>
			<p class="th-empty">Chưa có bài viết.</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
