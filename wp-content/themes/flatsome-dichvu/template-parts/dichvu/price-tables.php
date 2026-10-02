<?php
/**
 * Chi phí trọn gói + bảng giá dạng bảng của dịch vụ. $args['id'] = ID dịch vụ.
 *
 * @package Flatsome_Dichvu
 */

defined( 'ABSPATH' ) || exit;

$sgd_id     = isset( $args['id'] ) ? absint( $args['id'] ) : get_the_ID();
$sgd_costs  = sgd_lines( sgd_meta( 'costs', $sgd_id ) );
$sgd_tables = sgd_price_tables( $sgd_id );
?>
<?php if ( $sgd_costs ) : ?>
	<div class="sgd-mtable sgd-mtable--costs">
		<p class="sgd-mtable__title">Chi phí trọn gói – <?php echo esc_html( get_the_title( $sgd_id ) ); ?></p>
		<div class="sgd-mtable__scroll">
		<table class="sgd-t">
			<thead><tr><th scope="col">Hạng mục</th><th scope="col">Chi phí</th></tr></thead>
			<tbody>
				<?php foreach ( $sgd_costs as $sgd_c ) : ?>
					<tr<?php echo 0 === mb_stripos( $sgd_c[0], 'tổng' ) ? ' class="is-total"' : ''; ?>>
						<th scope="row"><?php echo esc_html( $sgd_c[0] ); ?></th>
						<td><?php echo esc_html( $sgd_c[1] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
	</div>
<?php endif; ?>
<?php foreach ( $sgd_tables as $sgd_t ) : ?>
	<?php $sgd_cols = max( count( $sgd_t['head'] ), $sgd_t['rows'] ? max( array_map( 'count', $sgd_t['rows'] ) ) : 0 ); ?>
	<div class="sgd-mtable">
		<?php if ( $sgd_t['title'] ) : ?>
			<p class="sgd-mtable__title"><?php echo esc_html( $sgd_t['title'] ); ?></p>
		<?php endif; ?>
		<div class="sgd-mtable__scroll">
		<table class="sgd-t">
			<?php if ( $sgd_t['head'] ) : ?>
				<thead><tr>
					<?php foreach ( $sgd_t['head'] as $sgd_h ) : ?>
						<th scope="col"><?php echo esc_html( $sgd_h ); ?></th>
					<?php endforeach; ?>
				</tr></thead>
			<?php endif; ?>
			<tbody>
				<?php
				// Ô đầu để trống → gộp với ô phía trên (rowspan), dùng cho bảng chia theo nhóm.
				$sgd_spans = array();
				$sgd_last  = -1;
				foreach ( $sgd_t['rows'] as $sgd_k => $sgd_r ) {
					if ( '' === $sgd_r[0] && $sgd_last >= 0 ) {
						++$sgd_spans[ $sgd_last ];
						$sgd_spans[ $sgd_k ] = 0;
					} else {
						$sgd_spans[ $sgd_k ] = 1;
						$sgd_last            = $sgd_k;
					}
				}
				?>
				<?php foreach ( $sgd_t['rows'] as $sgd_k => $sgd_r ) : ?>
					<tr>
						<?php foreach ( $sgd_r as $sgd_i => $sgd_cell ) : ?>
							<?php
							if ( 0 === $sgd_i && 0 === $sgd_spans[ $sgd_k ] ) {
								continue;
							}
							?>
							<?php
							// Dòng ít ô hơn số cột: ô cuối trải hết phần còn lại.
							$sgd_span = ( count( $sgd_r ) - 1 === $sgd_i && count( $sgd_r ) < $sgd_cols ) ? $sgd_cols - $sgd_i : 1;
							?>
							<?php if ( 0 === $sgd_i ) : ?>
								<th scope="row"<?php echo $sgd_spans[ $sgd_k ] > 1 ? ' rowspan="' . esc_attr( $sgd_spans[ $sgd_k ] ) . '"' : ''; ?>><?php echo esc_html( $sgd_cell ); ?></th>
							<?php else : ?>
								<td<?php echo $sgd_span > 1 ? ' colspan="' . esc_attr( $sgd_span ) . '"' : ''; ?>><?php echo esc_html( $sgd_cell ); ?></td>
							<?php endif; ?>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php foreach ( $sgd_t['notes'] as $sgd_n ) : ?>
			<p class="sgd-mtable__note">(*) <?php echo esc_html( $sgd_n ); ?></p>
		<?php endforeach; ?>
	</div>
<?php endforeach; ?>
