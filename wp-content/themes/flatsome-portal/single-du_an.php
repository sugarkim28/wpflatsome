<?php
/**
 * Trang chi tiết dự án – cấu trúc chuẩn SEO:
 * H1 (tên dự án) → bảng thông tin → điểm nổi bật → mục lục + nội dung (H2) → ảnh → bản đồ → FAQ → dự án liên quan.
 *
 * @package Flatsome_Portal
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$sgp_id     = get_the_ID();
	$sgp_status = sgp_first_term( 'trang_thai' );
	$sgp_type   = sgp_first_term( 'loai_hinh' );
	$sgp_area   = sgp_first_term( 'khu_vuc' );
	$sgp_dev    = sgp_first_term( 'chu_dau_tu' );
	$sgp_bg     = has_post_thumbnail() ? get_the_post_thumbnail_url( $sgp_id, 'full' ) : '';
	?>
	<div id="content" class="sgp-single">
		<header class="sgp-phero"<?php echo $sgp_bg ? ' style="background-image:url(' . esc_url( $sgp_bg ) . ')"' : ''; ?>>
			<div class="sgp-phero__inner container">
				<?php sgp_breadcrumbs(); ?>
				<p class="sgp-phero__badges">
					<?php if ( $sgp_status ) : ?>
						<span class="sgp-badge sgp-badge--<?php echo esc_attr( $sgp_status->slug ); ?>"><?php echo esc_html( $sgp_status->name ); ?></span>
					<?php endif; ?>
					<?php if ( $sgp_type ) : ?>
						<a class="sgp-chip" href="<?php echo esc_url( get_term_link( $sgp_type ) ); ?>"><?php echo esc_html( $sgp_type->name ); ?></a>
					<?php endif; ?>
				</p>
				<h1 class="sgp-phero__title"><?php the_title(); ?></h1>
				<?php if ( sgp_meta( 'subtitle' ) ) : ?>
					<p class="sgp-phero__sub"><?php echo esc_html( sgp_meta( 'subtitle' ) ); ?></p>
				<?php endif; ?>
				<?php if ( sgp_meta( 'price' ) ) : ?>
					<p class="sgp-phero__price"><?php echo esc_html( sgp_meta( 'price' ) ); ?><?php echo sgp_meta( 'policy' ) ? ' · ' . esc_html( sgp_meta( 'policy' ) ) : ''; ?></p>
				<?php endif; ?>
				<p class="sgp-phero__actions">
					<a class="button sgp-btn" href="#dang-ky">Nhận bảng giá &amp; chính sách</a>
					<?php if ( sgp_meta( 'landing' ) ) : ?>
						<a class="button sgp-btn-ghost" href="<?php echo esc_url( sgp_meta( 'landing' ) ); ?>" target="_blank" rel="noopener">Website dự án</a>
					<?php endif; ?>
				</p>
			</div>
		</header>

		<div class="row sgp-single__row">
			<div class="col large-8 sgp-single__main">
				<section class="sgp-facts" id="thong-tin">
					<h2>Thông tin dự án <?php the_title(); ?></h2>
					<table>
						<tbody>
							<?php
							$sgp_rows = array(
								'Chủ đầu tư' => $sgp_dev ? '<a href="' . esc_url( get_term_link( $sgp_dev ) ) . '">' . esc_html( $sgp_dev->name ) . '</a>' : '',
								'Vị trí'     => esc_html( sgp_meta( 'address' ) ) . ( $sgp_area ? ' – <a href="' . esc_url( get_term_link( $sgp_area ) ) . '">' . esc_html( $sgp_area->name ) . '</a>' : '' ),
								'Loại hình'  => $sgp_type ? esc_html( $sgp_type->name ) : '',
								'Quy mô'     => esc_html( sgp_meta( 'scale' ) ),
								'Diện tích'  => esc_html( sgp_meta( 'area' ) ),
								'Giá bán'    => esc_html( sgp_meta( 'price' ) ),
								'Bàn giao'   => esc_html( sgp_meta( 'handover' ) ),
								'Pháp lý'    => esc_html( sgp_meta( 'legal' ) ),
								'Cập nhật'   => esc_html( get_the_modified_date( 'd/m/Y' ) ),
							);
							foreach ( $sgp_rows as $sgp_label => $sgp_val ) {
								if ( '' !== trim( wp_strip_all_tags( $sgp_val ) ) ) {
									echo '<tr><th scope="row">' . esc_html( $sgp_label ) . '</th><td>' . wp_kses_post( $sgp_val ) . '</td></tr>';
								}
							}
							?>
						</tbody>
					</table>
				</section>

				<?php $sgp_hl = sgp_lines( sgp_meta( 'highlights' ) ); ?>
				<?php if ( $sgp_hl ) : ?>
					<section class="sgp-highlights" id="diem-noi-bat">
						<h2>Điểm nổi bật</h2>
						<div class="sgp-grid sgp-grid--2">
							<?php foreach ( $sgp_hl as $sgp_h ) : ?>
								<div class="sgp-hl"><h3><?php echo esc_html( $sgp_h[0] ); ?></h3><?php echo $sgp_h[1] ? '<p>' . esc_html( $sgp_h[1] ) . '</p>' : ''; ?></div>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<div class="sgp-content entry-content"><?php the_content(); ?></div>

				<?php $sgp_gallery = sgp_meta( 'gallery' ); ?>
				<?php if ( $sgp_gallery ) : ?>
					<section class="sgp-gallery" id="hinh-anh">
						<h2>Hình ảnh dự án <?php the_title(); ?></h2>
						<?php echo do_shortcode( '[ux_gallery ids="' . esc_attr( $sgp_gallery ) . '" style="normal" columns="3" columns__sm="2" col_spacing="xsmall" image_height="70%" image_size="medium_large"]' ); ?>
					</section>
				<?php endif; ?>

				<?php if ( sgp_meta( 'map' ) ) : ?>
					<section class="sgp-map" id="ban-do">
						<h2>Vị trí trên bản đồ</h2>
						<div class="sgp-map__frame"><iframe src="<?php echo esc_url( sgp_meta( 'map' ) ); ?>" title="Bản đồ <?php the_title_attribute(); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
					</section>
				<?php endif; ?>

				<?php $sgp_faq = sgp_lines( sgp_meta( 'faq' ) ); ?>
				<?php if ( $sgp_faq ) : ?>
					<section class="sgp-faq" id="hoi-dap">
						<h2>Câu hỏi thường gặp về <?php the_title(); ?></h2>
						<?php foreach ( $sgp_faq as $sgp_i => $sgp_f ) : ?>
							<details<?php echo 0 === $sgp_i ? ' open' : ''; ?>><summary><h3><?php echo esc_html( $sgp_f[0] ); ?></h3></summary><p><?php echo esc_html( $sgp_f[1] ); ?></p></details>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>

				<p class="sgp-disclaimer"><?php echo esc_html( sgp_opt( 'disclaimer' ) ); ?> Cập nhật lần cuối: <?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?>.</p>
			</div>

			<aside class="col large-4 sgp-single__side">
				<div class="sgp-sticky">
					<div class="sgp-card-form" id="dang-ky">
						<?php
						get_template_part(
							'template-parts/portal/lead-form',
							null,
							array(
								'title'   => 'Nhận bảng giá ' . get_the_title(),
								'button'  => 'Nhận bảng giá ngay',
								'source'  => 'Trang dự án',
								'project' => $sgp_id,
							)
						);
						?>
					</div>
					<div class="sgp-callbtns sgp-callbtns--stack"><?php echo sgp_call_buttons( $sgp_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm. ?></div>
				</div>
			</aside>
		</div>

		<?php $sgp_rel = sgp_related_projects( $sgp_id, 3 ); ?>
		<?php if ( $sgp_rel ) : ?>
			<section class="sgp-related">
				<div class="container">
					<h2>Dự án cùng khu vực &amp; tương tự</h2>
					<div class="sgp-grid sgp-grid--3">
						<?php
						global $post;
						foreach ( $sgp_rel as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $post );
							get_template_part( 'template-parts/portal/card', null, array( 'tag' => 'h3' ) );
						endforeach;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
