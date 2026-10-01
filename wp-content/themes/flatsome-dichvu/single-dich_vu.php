<?php
/**
 * Trang chi tiết dịch vụ – bố cục dạng bài viết (cột nội dung + cột phải):
 * Breadcrumb → H1 → ngày cập nhật, người kiểm duyệt → đoạn mở đầu → ô tóm tắt (phí, thời gian, nút) → mục lục → chi phí & bảng giá → Gọi ngay →
 * công việc thực hiện → hồ sơ cần chuẩn bị → quy trình → nội dung (H2) → FAQ → dịch vụ cùng nhóm.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$sgd_id       = get_the_ID();
	$sgd_group    = sgd_first_group();
	$sgd_pkgs     = sgd_packages();
	$sgd_has_cost = sgd_meta( 'costs' ) || sgd_price_tables() || $sgd_pkgs;
	$sgd_includes = sgd_list( sgd_meta( 'includes' ) );
	$sgd_docs     = sgd_list( sgd_meta( 'documents' ) );
	$sgd_steps    = sgd_lines( sgd_meta( 'process' ) );
	$sgd_faq      = sgd_lines( sgd_meta( 'faq' ) );
	$sgd_title    = get_the_title();
	list( $sgd_content, $sgd_heads ) = sgd_content_headings( apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook lõi WP.

	$sgd_toc = array();
	if ( $sgd_has_cost ) {
		$sgd_toc[] = array( 'bang-gia', 'Chi phí & bảng giá ' . $sgd_title );
	}
	if ( $sgd_includes ) {
		$sgd_toc[] = array( 'cong-viec', 'Công việc chúng tôi thực hiện' );
	}
	if ( $sgd_docs ) {
		$sgd_toc[] = array( 'ho-so', 'Thông tin, hồ sơ bạn cần chuẩn bị' );
	}
	if ( $sgd_steps ) {
		$sgd_toc[] = array( 'quy-trinh', 'Quy trình & thời gian thực hiện' );
	}
	$sgd_toc = array_merge( $sgd_toc, $sgd_heads );
	if ( $sgd_faq ) {
		$sgd_toc[] = array( 'hoi-dap', 'Câu hỏi thường gặp' );
	}
	$sgd_intro = has_excerpt() ? get_the_excerpt() : sgd_meta( 'subtitle' );
	?>
	<div id="content" class="sgd-single">
		<div class="row sgd-single__row">
			<div class="col large-8 sgd-single__main">
				<?php sgd_breadcrumbs(); ?>
				<h1 class="sgd-single__title"><?php the_title(); ?></h1>
				<p class="sgd-byline">
					Cập nhật <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></time>
					<?php if ( sgd_opt( 'expert' ) ) : ?>
						· Kiểm duyệt nội dung: <strong><?php echo esc_html( trim( sgd_opt( 'expert_title' ) . ' ' . sgd_opt( 'expert' ) ) ); ?></strong>
					<?php endif; ?>
				</p>
				<?php if ( $sgd_intro ) : ?>
					<p class="sgd-single__intro"><?php echo esc_html( wp_strip_all_tags( $sgd_intro ) ); ?></p>
				<?php endif; ?>

				<div class="sgd-summary">
					<dl class="sgd-summary__facts">
						<div><dt><?php echo sgd_icon( 'wallet' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Phí dịch vụ</dt><dd class="is-price"><?php echo esc_html( sgd_meta( 'price' ) ? sgd_meta( 'price' ) : 'Liên hệ' ); ?></dd></div>
						<?php if ( sgd_meta( 'duration' ) ) : ?>
							<div><dt><?php echo sgd_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Thời gian</dt><dd><?php echo esc_html( sgd_meta( 'duration' ) ); ?></dd></div>
						<?php endif; ?>
						<div><dt><?php echo sgd_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Cam kết</dt><dd>Trọn gói, không phát sinh</dd></div>
					</dl>
					<p class="sgd-summary__btns">
						<a class="button sgd-btn" href="#dang-ky">Nhận báo giá</a>
						<a class="button sgd-btn is-outline" href="tel:<?php echo esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ); ?>"><?php echo sgd_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sgd_opt( 'hotline' ) ); ?></a>
					</p>
				</div>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="sgd-single__img"><?php the_post_thumbnail( 'large', array( 'alt' => esc_attr( get_the_title() ), 'fetchpriority' => 'high' ) ); ?></figure>
				<?php endif; ?>

				<?php if ( count( $sgd_toc ) >= 2 ) : ?>
					<nav class="sgd-toc" aria-label="Mục lục">
						<p class="sgd-toc__title">Nội dung chính:</p>
						<ul>
							<?php foreach ( $sgd_toc as $sgd_t ) : ?>
								<li><a href="#<?php echo esc_attr( $sgd_t[0] ); ?>"><?php echo esc_html( $sgd_t[1] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>

				<?php if ( $sgd_has_cost ) : ?>
					<section id="bang-gia" class="sgd-sec">
						<h2>Chi phí &amp; bảng giá <?php echo esc_html( $sgd_title ); ?></h2>
						<?php get_template_part( 'template-parts/dichvu/price-tables', null, array( 'id' => $sgd_id ) ); ?>
						<?php get_template_part( 'template-parts/dichvu/packages', null, array( 'id' => $sgd_id ) ); ?>
						<?php get_template_part( 'template-parts/dichvu/call-now' ); ?>
					</section>
				<?php endif; ?>

				<?php if ( $sgd_includes ) : ?>
					<section id="cong-viec" class="sgd-sec">
						<h2>Công việc chúng tôi thực hiện</h2>
						<ul class="sgd-check">
							<?php foreach ( $sgd_includes as $sgd_i ) : ?>
								<li><?php echo esc_html( $sgd_i ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( $sgd_docs ) : ?>
					<section id="ho-so" class="sgd-sec">
						<h2>Thông tin, hồ sơ bạn cần chuẩn bị</h2>
						<ol class="sgd-numlist">
							<?php foreach ( $sgd_docs as $sgd_i ) : ?>
								<li><?php echo esc_html( $sgd_i ); ?></li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>

				<?php if ( $sgd_steps ) : ?>
					<section id="quy-trinh" class="sgd-sec">
						<h2>Quy trình &amp; thời gian thực hiện</h2>
						<?php if ( sgd_meta( 'duration' ) ) : ?>
							<p>Tổng thời gian hoàn thành: <strong><?php echo esc_html( sgd_meta( 'duration' ) ); ?></strong>.</p>
						<?php endif; ?>
						<?php get_template_part( 'template-parts/dichvu/process', null, array( 'steps' => $sgd_steps ) ); ?>
						<?php get_template_part( 'template-parts/dichvu/call-now' ); ?>
					</section>
				<?php endif; ?>

				<div class="sgd-content entry-content"><?php echo $sgd_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- nội dung bài viết đã qua the_content. ?></div>

				<?php if ( $sgd_faq ) : ?>
					<section class="sgd-faq sgd-sec" id="hoi-dap">
						<h2>Câu hỏi thường gặp</h2>
						<?php foreach ( $sgd_faq as $sgd_i => $sgd_f ) : ?>
							<details<?php echo 0 === $sgd_i ? ' open' : ''; ?>><summary><h3><?php echo esc_html( $sgd_f[0] ); ?></h3></summary><p><?php echo esc_html( $sgd_f[1] ); ?></p></details>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>

				<p class="sgd-disclaimer"><?php echo esc_html( sgd_opt( 'disclaimer' ) ); ?> Cập nhật: <?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?>.</p>

				<?php if ( $sgd_group ) : ?>
					<p class="sgd-tags"><span>Chuyên mục:</span> <a href="<?php echo esc_url( get_term_link( $sgd_group ) ); ?>"><?php echo esc_html( $sgd_group->name ); ?></a> <a href="<?php echo esc_url( get_post_type_archive_link( 'dich_vu' ) ); ?>">Tất cả dịch vụ</a></p>
				<?php endif; ?>
			</div>

			<aside class="col large-4 sgd-single__side">
				<?php get_template_part( 'template-parts/dichvu/sidebar', null, array( 'service' => $sgd_id ) ); ?>
			</aside>
		</div>

		<?php $sgd_rel = sgd_related_services( $sgd_id, 4 ); ?>
		<?php if ( $sgd_rel ) : ?>
			<section class="sgd-related">
				<div class="container">
					<h2 class="sgd-htab"><span>Cùng chuyên mục</span></h2>
					<div class="sgd-grid sgd-grid--4">
						<?php
						global $post;
						foreach ( $sgd_rel as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $post );
							get_template_part( 'template-parts/dichvu/card', null, array( 'tag' => 'h3' ) );
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
