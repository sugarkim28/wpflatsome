<?php
/**
 * Kiểm thử tự động (chạy trên site WordPress THỬ NGHIỆM còn trống – tạo dữ liệu mẫu rồi kiểm tra).
 *
 *   wp plugin activate quan-ly-doanh-nghiep
 *   wp eval-file wp-content/plugins/quan-ly-doanh-nghiep/tests/run-tests.php --user=admin
 *
 * Không chạy trên site đang dùng thật: bài kiểm tra tạo đơn hàng, phiếu thu chi, đơn từ, chốt lương.
 *
 * @package QLDN
 */

defined( 'ABSPATH' ) || exit;

if ( qldn_is_empty() ) {
	$demo = qldn_demo_create();
	if ( is_wp_error( $demo ) ) {
		echo 'Không tạo được dữ liệu mẫu: ' . $demo->get_error_message() . "\n";
		return;
	}
}
define( 'QLDN_TESTING', true );
global $wpdb; $fail=0; function ok($c,$m){ global $fail; echo ($c?"PASS ":"FAIL ").$m."\n"; if(!$c)$fail++; }
add_filter( 'pre_wp_mail', '__return_false' );
wp_set_current_user(1);
$E = function($pin){ global $wpdb; return (int)$wpdb->get_var($wpdb->prepare("SELECT id FROM ".qldn_t('employees')." WHERE device_pin=%s",$pin)); };
$CB = qldn_t('cashbook');
// ---------- Sổ quỹ ----------
$today = qldn_today();
$bal0 = qldn_fund_balance( $today, '', false );
$exp = (int)$wpdb->get_var("SELECT SUM(CASE WHEN direction='in' THEN amount ELSE -amount END) FROM $CB");
ok( $bal0 === $exp, "fund balance = sum entries ($bal0)" );
$paidSum = (int)$wpdb->get_var("SELECT SUM(paid) FROM ".qldn_t('orders'));
$cashOrders = (int)$wpdb->get_var("SELECT SUM(amount) FROM $CB WHERE ref_type='order' AND category='Thu tiền bán hàng'");
ok( $paidSum === $cashOrders, "every paid order has a receipt ($paidSum)" );
$pid = (int)$wpdb->get_var("SELECT id FROM ".qldn_t('products')." LIMIT 1");
$cid = (int)$wpdb->get_var("SELECT id FROM ".qldn_t('customers')." WHERE ctype='company'");
$debt0 = (int) qldn_get('customers',$cid)->debt;
$o = qldn_order_create( array('customer_id'=>$cid,'lines'=>array(array('product_id'=>$pid,'qty'=>2,'price'=>100000)),'paid'=>50000,'payment'=>'transfer') );
$pt = $wpdb->get_row("SELECT * FROM $CB WHERE ref_type='order' AND ref_id=$o");
ok( $pt && (int)$pt->amount === 50000 && $pt->fund === 'bank' && $pt->direction === 'in' && strpos($pt->code,'PT')===0, 'order receipt bank 50k ' . ($pt?$pt->code:'') );
$got = qldn_customer_collect( $cid, 1000000000, 'cash' );
ok( $got === $debt0 + 150000 && (int) qldn_get('customers',$cid)->debt === 0, "collect all customer debt ($got)" );
ok( (int)$wpdb->get_var("SELECT COUNT(*) FROM $CB WHERE category='Thu nợ khách hàng' AND partner_id=$cid") >= 1, 'debt receipts created' );
qldn_order_cancel( $o, 'test' );
$pc = $wpdb->get_row("SELECT * FROM $CB WHERE ref_type='order' AND ref_id=$o AND direction='out'");
ok( $pc && (int)$pc->amount === 200000 && $pc->category === 'Hoàn tiền khách', 'cancel paid order -> refund payment slip' );
ok( (int) qldn_get('customers',$cid)->debt === 0, 'customer debt stays 0 after cancel of fully paid order' );
// contract payment
$k = (int)$wpdb->get_var("SELECT id FROM ".qldn_t('contracts')." WHERE type='sale'");
do_action( 'qldn_contract_paid', $k, 3000000, 'bank' );
ok( (int)$wpdb->get_var("SELECT amount FROM $CB WHERE ref_type='contract' AND ref_id=$k") === 3000000, 'contract payment receipt' );
// supplier
$sid = (int)$wpdb->get_var("SELECT id FROM ".qldn_t('suppliers')." ORDER BY id LIMIT 1");
$s0 = qldn_get('suppliers',$sid);
$ref = qldn_stock_voucher( 'nhap', array( array('product_id'=>$pid,'qty'=>10,'unit_cost'=>50000) ) );
qldn_purchase_record( $ref, $sid, array( array('product_id'=>$pid,'qty'=>10,'unit_cost'=>50000) ), 200000, 'cash' );
$s1 = qldn_get('suppliers',$sid);
ok( $s1->debt - $s0->debt === 300000 && $s1->total_purchase - $s0->total_purchase === 500000, 'purchase raises supplier debt by unpaid part' );
ok( (int)$wpdb->get_var("SELECT amount FROM $CB WHERE ref_type='purchase' ORDER BY id DESC LIMIT 1") === 200000, 'purchase payment slip' );
qldn_supplier_pay( $sid, (int)$s1->debt, 'bank' );
ok( (int) qldn_get('suppliers',$sid)->debt === 0, 'supplier paid off' );
ok( is_wp_error( qldn_cash_add( array('direction'=>'out','amount'=>0) ) ), 'zero amount rejected' );
// ---------- Ca, đi muộn, trừ lương ----------
$o2 = get_option('qldn_settings'); $o2['late_deduct']=1; $o2['auto_ot']=0; update_option('qldn_settings',$o2);
$e3 = $E('3'); // Lê Minh Châu, ca mặc định 08:00-17:00, lương 15tr
qldn_punches_save( array( array('pin'=>'3','time'=>'2026-10-07 08:20:00'), array('pin'=>'3','time'=>'2026-10-07 16:50:00') ), 'file' );
$a = $wpdb->get_row("SELECT * FROM ".qldn_t('attendance')." WHERE employee_id=$e3 AND work_date='2026-10-07'");
ok( (int)$a->late_min === 20 && (int)$a->early_min === 10 && $a->mark === 'X', "late 20 / early 10 ({$a->late_min}/{$a->early_min})" );
qldn_punches_save( array( array('pin'=>'3','time'=>'2026-10-08 08:04:00'), array('pin'=>'3','time'=>'2026-10-08 17:00:00') ), 'file' );
ok( (int)$wpdb->get_var("SELECT late_min FROM ".qldn_t('attendance')." WHERE employee_id=$e3 AND work_date='2026-10-08'") === 0, 'within 5 min grace -> 0' );
qldn_payroll_build('2026-10');
$pr = $wpdb->get_row("SELECT * FROM ".qldn_t('payroll')." WHERE period='2026-10' AND employee_id=$e3");
$hourly = 15000000/26/8; $expd = (int) round($hourly/60*30);
ok( (int)$pr->late_min === 30 && (int)$pr->late_deduct === $expd && (int)$pr->gross === (int)$pr->time_pay - $expd + (int)$pr->allowance + (int)$pr->commission + (int)$pr->ot_pay + (int)$pr->bonus, "payroll deducts actual minutes: $expd" );
// night shift
$e8 = $E('8');
qldn_punches_save( array( array('pin'=>'8','time'=>'2026-10-09 21:58:00'), array('pin'=>'8','time'=>'2026-10-10 05:40:00') ), 'file' );
$n = $wpdb->get_row("SELECT * FROM ".qldn_t('attendance')." WHERE employee_id=$e8 AND work_date='2026-10-09'");
ok( $n && $n->check_in === '21:58:00' && $n->check_out === '05:40:00' && (int)$n->early_min === 20 && ! $wpdb->get_var("SELECT id FROM ".qldn_t('attendance')." WHERE employee_id=$e8 AND work_date='2026-10-10'"), 'night shift: morning punch belongs to previous day, early 20' );
// ---------- Quỹ phép ----------
$e4 = $E('4'); $emp4 = qldn_get('employees',$e4);
$years = (int)(new DateTime($emp4->start_date))->diff(new DateTime(date('Y').'-12-31'))->y;
ok( qldn_leave_entitlement( $emp4, (int)date('Y') ) == 12 + floor($years/5), "entitlement with seniority ($years y) = " . qldn_leave_entitlement( $emp4, (int)date('Y') ) );
$tmp = (object) array('start_date' => date('Y').'-08-10');
ok( qldn_leave_entitlement( $tmp, (int)date('Y') ) == 5.0, 'new joiner Aug 10 -> 5 days (5 months)' );
$tmp2 = (object) array('start_date' => date('Y').'-08-20');
ok( qldn_leave_entitlement( $tmp2, (int)date('Y') ) == 4.0, 'new joiner Aug 20 -> 4 days (Aug not counted)' );
// ---------- Đơn từ & duyệt ----------
// tạo tài khoản cho quản lý (Châu = manager của Dung pin 4) và cho Dung
$u3 = wp_insert_user( array('user_login'=>'chau','user_pass'=>'chau','role'=>'qldn_staff') );
$u4 = wp_insert_user( array('user_login'=>'dung','user_pass'=>'dung','role'=>'qldn_staff') );
$wpdb->update( qldn_t('employees'), array('user_id'=>$u3), array('id'=>$e3) );
$wpdb->update( qldn_t('employees'), array('user_id'=>$u4), array('id'=>$e4) );
wp_set_current_user( $u4 );
$r1 = qldn_request_create( array('employee_id'=>$e4,'type'=>'annual','from_date'=>'2026-12-14','to_date'=>'2026-12-20','reason'=>'Du lịch') );
ok( ! is_wp_error($r1) && (float) qldn_get('requests',$r1)->days === 6.0, 'annual leave Mon-Sun = 6 workdays (Sunday off)' );
ok( is_wp_error( qldn_request_create( array('employee_id'=>$e4,'type'=>'unpaid','from_date'=>'2026-12-16','reason'=>'x') ) ), 'overlap rejected' );
ok( is_wp_error( qldn_request_create( array('employee_id'=>$e4,'type'=>'annual','from_date'=>'2026-12-21','to_date'=>'2026-12-31','reason'=>'x') ) ), 'exceeding balance rejected' );
ok( is_wp_error( qldn_request_decide( $r1, 'approved' ) ), 'cannot approve own request' );
wp_set_current_user( $u3 );
$d = qldn_request_decide( $r1, 'approved', 'OK' );
ok( true === $d, 'direct manager approves' );
ok( (int)$wpdb->get_var("SELECT COUNT(*) FROM ".qldn_t('attendance')." WHERE employee_id=$e4 AND mark='P' AND work_date BETWEEN '2026-12-14' AND '2026-12-20'") === 6, 'attendance P x6 written' );
$b = qldn_leave_balance( $e4, 2026 );
ok( $b['used'] == 6, 'balance used 6' );
$e6 = $E('6'); // Giang, manager = An (admin)
$r2 = qldn_request_create( array('employee_id'=>$e6,'type'=>'annual','from_date'=>'2026-12-15','half_day'=>1,'reason'=>'x') );
ok( is_wp_error( qldn_request_decide( $r2, 'approved' ) ), 'non-manager cannot approve' );
wp_set_current_user(1);
ok( true === qldn_request_decide( $r2, 'approved' ) && $wpdb->get_var("SELECT mark FROM ".qldn_t('attendance')." WHERE employee_id=$e6 AND work_date='2026-12-15'") === 'H', 'HR approves half day -> H' );
ok( true === qldn_request_decide( $r1, 'cancelled' ) && 0 === (int)$wpdb->get_var("SELECT COUNT(*) FROM ".qldn_t('attendance')." WHERE employee_id=$e4 AND mark='P' AND work_date BETWEEN '2026-12-14' AND '2026-12-20'"), 'HR cancels approved -> marks cleared' );
$r3 = qldn_request_create( array('employee_id'=>$e3,'type'=>'explain','from_date'=>'2026-10-07','reason'=>'Kẹt xe do tai nạn') );
qldn_request_decide( $r3, 'approved' );
ok( (int)$wpdb->get_var("SELECT late_min+early_min FROM ".qldn_t('attendance')." WHERE employee_id=$e3 AND work_date='2026-10-07'") === 0, 'explanation approved clears late' );
$r4 = qldn_request_create( array('employee_id'=>$e3,'type'=>'ot','from_date'=>'2026-10-08','hours'=>2.5,'reason'=>'Kiểm kê') );
qldn_request_decide( $r4, 'approved' );
ok( (float)$wpdb->get_var("SELECT ot_hours FROM ".qldn_t('attendance')." WHERE employee_id=$e3 AND work_date='2026-10-08'") === 2.5, 'OT request adds hours' );
qldn_payroll_set_status('2026-10','locked');
$r5 = qldn_request_create( array('employee_id'=>$e3,'type'=>'sick','from_date'=>'2026-10-20','reason'=>'ốm') );
ok( is_wp_error( qldn_request_decide( $r5, 'approved' ) ), 'cannot approve into locked month' );
do_action('qldn_payroll_paid','2026-10'); do_action('qldn_payroll_paid','2026-10');
ok( (int)$wpdb->get_var("SELECT COUNT(*) FROM $CB WHERE ref_type='payroll' AND ref_id=202610") === 1 && (int)$wpdb->get_var("SELECT amount FROM $CB WHERE ref_type='payroll' AND ref_id=202610") === (int)$wpdb->get_var("SELECT SUM(net) FROM ".qldn_t('payroll')." WHERE period='2026-10' AND net>0"), 'payroll paid -> one payment slip = total net' );
// ---------- Tự chấm công ----------
wp_set_current_user( $u4 );
$wpdb->query("DELETE FROM ".qldn_t('attendance')." WHERE employee_id=$e4 AND work_date='$today'");
$c1 = qldn_self_checkin( $u4 );
$row = $wpdb->get_row("SELECT * FROM ".qldn_t('attendance')." WHERE employee_id=$e4 AND work_date='$today'");
ok( 'in' === $c1 && $row && $row->check_in && (int)$wpdb->get_var("SELECT COUNT(*) FROM ".qldn_t('punches')." WHERE employee_id=$e4 AND source='web'") >= 1, 'web check-in recorded as punch' );
wp_set_current_user(1);
// ---------- Nhật ký ----------
ok( (int)$wpdb->get_var("SELECT COUNT(*) FROM ".qldn_t('logs')." WHERE action IN ('request_decide','supplier_pay','order_cancel','payroll_paid')") >= 5, 'audit log entries' );
// ---------- Chữ số ----------
ok( qldn_money_words(1500000) === 'Một triệu năm trăm nghìn đồng', 'money words still ok' );
echo $fail ? "$fail FAILED\n" : "ALL PASSED\n";
