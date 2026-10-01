<?php
/**
 * Trang chi tiết dịch vụ – cấu trúc chuẩn SEO:
 * H1 → giá / thời gian → bảng giá theo gói → công việc & hồ sơ → quy trình → nội dung (H2) → FAQ → dịch vụ liên quan.
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
	$sgd_includes = sgd_list( sgd_meta( 'includes' ) );
	$sgd_docs     = sgd_list( sgd_meta( 'documents' ) );
	$sgd_steps    = sgd_lines( sgd_meta( 'process' ) );
	$sgd_faq      = sgd_lines( sgd_meta( 'faq' ) );
	$sgd_title    = get_the_title();
	list( $sgd_content, $sgd_heads ) = sgd_content_headings( apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hook lõi WP.

	$sgd_toc = array();
	if ( $sgd_pkgs ) {
		$sgd_toc[] = array( 'bang-gia', 'Bảng giá dịch vụ' );
	}
	if ( $sgd_includes || $sgd_docs ) {
		$sgd_toc[] = array( 'cong-viec-ho-so', 'Công việc & hồ sơ cần chuẩn bị' );
	}
	if ( $sgd_steps ) {
		$sgd_toc[] = array( 'quy-trinh', 'Quy trình thực hiện' );
	}
	$sgd_toc = array_merge( $sgd_toc, $sgd_heads );
	if ( $sgd_faq ) {
		$sgd_toc[] = array( 'hoi-dap', 'Câu hỏi thường gặp' );
	}
	?>
	<div id="content" class="sgd-single">
		<header class="sgd-phero">
			<div class="sgd-phero__inner container">
				<?php sgd_breadcrumbs(); ?>
				<?php if ( $sgd_group ) : ?>
					<p class="sgd-phero__group"><a href="<?php echo esc_url( get_term_link( $sgd_group ) ); ?>"><?php echo esc_html( $sgd_group->name ); ?></a></p>
				<?php endif; ?>
				<h1 class="sgd-phero__title"><?php the_title(); ?></h1>
				<?php if ( sgd_meta( 'subtitle' ) ) : ?>
					<p class="sgd-phero__sub"><?php echo esc_html( sgd_meta( 'subtitle' ) ); ?></p>
				<?php endif; ?>
				<ul class="sgd-phero__facts">
					<?php if ( sgd_meta( 'price' ) ) : ?>
						<li><?php echo sgd_icon( 'wallet' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><small>Phí dịch vụ</small><strong><?php echo esc_html( sgd_meta( 'price' ) ); ?></strong></span></li>
					<?php endif; ?>
					<?php if ( sgd_meta( 'duration' ) ) : ?>
						<li><?php echo sgd_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><small>Thời gian</small><strong><?php echo esc_html( sgd_meta( 'duration' ) ); ?></strong></span></li>
					<?php endif; ?>
					<li><?php echo sgd_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><small>Cam kết</small><strong>Trọn gói, không phát sinh</strong></span></li>
				</ul>
				<p class="sgd-phero__actions">
					<a class="button sgd-btn" href="#dang-ky">Nhận tư vấn miễn phí</a>
					<a class="button sgd-btn-ghost" href="tel:<?php echo esc_attr( sgd_tel( sgd_opt( 'hotline' ) ) ); ?>">Gọi <?php echo esc_html( sgd_opt( 'hotline' ) ); ?></a>
				</p>
			</div>
		</header>

		<div class="row sgd-single__row">
			<div class="col large-8 sgd-single__main">
				<?php if ( count( $sgd_toc ) >= 3 ) : ?>
					<nav class="sgd-toc" aria-label="Mục lục">
						<p class="sgd-toc__title">Nội dung chính</p>
						<ol>
							<?php foreach ( $sgd_toc as $sgd_t ) : ?>
								<li><a href="#<?php echo esc_attr( $sgd_t[0] ); ?>"><?php echo esc_html( $sgd_t[1] ); ?></a></li>
							<?php endforeach; ?>
						</ol>
					</nav>
				<?php endif; ?>

				<?php if ( $sgd_pkgs ) : ?>
					<section id="bang-gia" class="sgd-sec">
						<h2>Bảng giá <?php echo esc_html( $sgd_title ); ?></h2>
						<?php get_template_part( 'template-parts/dichvu/packages', null, array( 'id' => $sgd_id ) ); ?>
					</section>
				<?php endif; ?>

				<?php if ( $sgd_includes || $sgd_docs ) : ?>
					<section id="cong-viec-ho-so" class="sgd-sec">
						<div class="sgd-grid sgd-grid--2">
							<?php if ( $sgd_includes ) : ?>
								<div class="sgd-box">
									<h2>Chúng tôi thực hiện</h2>
									<ul class="sgd-check">
										<?php foreach ( $sgd_includes as $sgd_i ) : ?>
											<li><?php echo esc_html( $sgd_i ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<?php if ( $sgd_docs ) : ?>
								<div class="sgd-box sgd-box--alt">
									<h2>Hồ sơ bạn cần chuẩn bị</h2>
									<ul class="sgd-check sgd-check--doc">
										<?php foreach ( $sgd_docs as $sgd_i ) : ?>
											<li><?php echo esc_html( $sgd_i ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( $sgd_steps ) : ?>
					<section id="quy-trinh" class="sgd-sec">
						<h2>Quy trình thực hiện</h2>
						<?php get_template_part( 'template-parts/dichvu/process', null, array( 'steps' => $sgd_steps ) ); ?>
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

				<p class="sgd-disclaimer"><?php echo esc_html( sgd_opt( 'disclaimer' ) ); ?> Cập nhật lần cuối: <?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?>.</p>
			</div>

			<aside class="col large-4 sgd-single__side">
				<div class="sgd-sticky">
					<div class="sgd-card-form" id="dang-ky">
						<?php
						get_template_part(
							'template-parts/dichvu/lead-form',
							null,
							array(
								'title'   => 'Báo giá ' . $sgd_title,
								'button'  => 'Nhận báo giá ngay',
								'source'  => 'Trang dịch vụ',
								'service' => $sgd_id,
							)
						);
						?>
					</div>
					<div class="sgd-callbtns sgd-callbtns--stack"><?php echo sgd_call_buttons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong hàm. ?></div>
				</div>
			</aside>
		</div>

		<?php $sgd_rel = sgd_related_services( $sgd_id, 3 ); ?>
		<?php if ( $sgd_rel ) : ?>
			<section class="sgd-related">
				<div class="container">
					<h2>Dịch vụ liên quan</h2>
					<div class="sgd-grid sgd-grid--3">
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
