<?php
/**
 * Câu hỏi thường gặp + dữ liệu có cấu trúc FAQPage cho Google.
 *
 * @package Flatsome_Child_BDS
 */

defined( 'ABSPATH' ) || exit;

$bds_faqs = array_filter(
	bds_lines( bds_opt( 'faq' ) ),
	function ( $row ) {
		return '' !== $row[1];
	}
);
if ( ! bds_opt( 'show_faq' ) || ! $bds_faqs ) {
	return;
}

$bds_schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array(),
);
?>
<section id="faq" class="bds-section bds-section--alt">
	<div class="container bds-faq">
		<div class="text-center bds-section__head">
			<h2 class="bds-heading"><?php echo esc_html( bds_opt( 'faq_title' ) ); ?></h2>
		</div>
		<?php foreach ( $bds_faqs as $bds_i => $bds_faq ) : ?>
			<?php
			$bds_schema['mainEntity'][] = array(
				'@type'          => 'Question',
				'name'           => $bds_faq[0],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $bds_faq[1],
				),
			);
			?>
			<details class="bds-faq__item"<?php echo 0 === $bds_i ? ' open' : ''; ?>>
				<summary><?php echo esc_html( $bds_faq[0] ); ?></summary>
				<p><?php echo esc_html( $bds_faq[1] ); ?></p>
			</details>
		<?php endforeach; ?>
	</div>
	<script type="application/ld+json"><?php echo wp_json_encode( $bds_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ); ?></script>
</section>
