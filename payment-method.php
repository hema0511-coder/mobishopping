<?php 
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/upi.php');
ensure_upi_columns($con);
if(strlen($_SESSION['login'])==0)
    {   
header('location:login.php');
}
else{
	$allowed = array('COD', 'Internet Banking', 'Debit / Credit card', 'UPI');
	$uid = (int)$_SESSION['id'];

	// Save a separate payment method for EACH purchased product (order row)
	if (isset($_POST['submit']) && !empty($_POST['paymethod']) && is_array($_POST['paymethod'])) {
		$stmt = mysqli_prepare($con, "UPDATE orders SET paymentMethod=?, paymentStatus=? WHERE id=? AND userId=? AND paymentMethod IS NULL");
		$hasUpi = false;
		foreach ($_POST['paymethod'] as $orderId => $method) {
			$orderId = (int)$orderId;
			if (!in_array($method, $allowed, true)) { $method = 'COD'; }
			$pstat = null;
			if ($method === 'UPI') { $pstat = 'Unpaid'; $hasUpi = true; }
			mysqli_stmt_bind_param($stmt, "ssii", $method, $pstat, $orderId, $uid);
			mysqli_stmt_execute($stmt);
		}
		mysqli_stmt_close($stmt);
		unset($_SESSION['cart']);
		// products paid by UPI go to the QR page, everything else to order history
		header($hasUpi ? 'location:upi-pay.php' : 'location:order-history.php');
		exit;
	}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Meta -->
		<meta charset="utf-8">
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
		<meta name="description" content="">
		<meta name="author" content="">
	    <meta name="keywords" content="MediaCenter, Template, eCommerce">
	    <meta name="robots" content="all">

	    <title>Mobi's Shopping | Payment Method</title>
	    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
	    <link rel="stylesheet" href="assets/css/main.css">
	    <link rel="stylesheet" href="assets/css/red.css">
	    <link rel="stylesheet" href="assets/css/owl.carousel.css">
		<link rel="stylesheet" href="assets/css/owl.transitions.css">
		<!--<link rel="stylesheet" href="assets/css/owl.theme.css">-->
		<link href="assets/css/lightbox.css" rel="stylesheet">
		<link rel="stylesheet" href="assets/css/animate.min.css">
		<link rel="stylesheet" href="assets/css/rateit.css">
		<link rel="stylesheet" href="assets/css/bootstrap-select.min.css">
		<link rel="stylesheet" href="assets/css/config.css">
		<link href="assets/css/green.css" rel="alternate stylesheet" title="Green color">
		<link href="assets/css/blue.css" rel="alternate stylesheet" title="Blue color">
		<link href="assets/css/red.css" rel="alternate stylesheet" title="Red color">
		<link href="assets/css/orange.css" rel="alternate stylesheet" title="Orange color">
		<link href="assets/css/dark-green.css" rel="alternate stylesheet" title="Darkgreen color">
		<link rel="stylesheet" href="assets/css/font-awesome.min.css">
		<link href='http://fonts.googleapis.com/css?family=Roboto:300,400,500,700' rel='stylesheet' type='text/css'>
		<link rel="shortcut icon" href="assets/images/favicon.ico">
	</head>
    <body class="cnt-home">
	
		
<header class="header-style-1">
<?php include('includes/top-header.php');?>
<?php include('includes/main-header.php');?>
<?php include('includes/menu-bar.php');?>
</header>
<div class="breadcrumb">
	<div class="container">
		<div class="breadcrumb-inner">
			<ul class="list-inline list-unstyled">
				<li><a href="home.html">Home</a></li>
				<li class='active'>Payment Method</li>
			</ul>
		</div><!-- /.breadcrumb-inner -->
	</div><!-- /.container -->
</div><!-- /.breadcrumb -->

<div class="body-content outer-top-bd">
	<div class="container">
		<div class="checkout-box faq-page inner-bottom-sm">
			<div class="row">
				<div class="col-md-12">
					<h2>Choose Payment Method</h2>
					<div class="panel-group checkout-steps" id="accordion">
						<!-- checkout-step-01  -->
<div class="panel panel-default checkout-step-01">

	<!-- panel-heading -->
		<div class="panel-heading">
    	<h4 class="unicase-checkout-title">
	        <a data-toggle="collapse" class="" data-parent="#accordion" href="#collapseOne">
	         Select a Payment Method for each product
	        </a>
	     </h4>
    </div>
    <!-- panel-heading -->

	<div id="collapseOne" class="panel-collapse collapse in">

		<!-- panel-body  -->
	    <div class="panel-body">
	    <form name="payment" method="post">
	    <?php
	    $stmt = mysqli_prepare($con, "SELECT orders.id AS orderid, orders.quantity AS qty, products.productName AS pname, products.productPrice AS pprice, products.shippingCharge AS ship FROM orders JOIN products ON orders.productId=products.id WHERE orders.userId=? AND orders.paymentMethod IS NULL");
	    mysqli_stmt_bind_param($stmt, "i", $uid);
	    mysqli_stmt_execute($stmt);
	    $res = mysqli_stmt_get_result($stmt);
	    $count = 0; $grand = 0;
	    ?>
	    <table class="table table-bordered">
	    	<thead>
	    		<tr><th>#</th><th>Product</th><th>Qty</th><th>Total (incl. shipping)</th><th>Payment Method</th></tr>
	    	</thead>
	    	<tbody>
	    	<?php while ($row = mysqli_fetch_assoc($res)) {
	    		$count++;
	    		$total = ($row['qty'] * $row['pprice']) + $row['ship'];
	    		$grand += $total;
	    		$oid = (int)$row['orderid'];
	    	?>
	    		<tr>
	    			<td><?php echo $count; ?></td>
	    			<td><?php echo htmlspecialchars($row['pname']); ?></td>
	    			<td><?php echo (int)$row['qty']; ?></td>
	    			<td>Rs. <?php echo $total; ?></td>
	    			<td>
	    				<label><input type="radio" name="paymethod[<?php echo $oid; ?>]" value="COD" checked="checked"> COD</label>&nbsp;
	    				<label><input type="radio" name="paymethod[<?php echo $oid; ?>]" value="Internet Banking"> Internet Banking</label>&nbsp;
	    				<label><input type="radio" name="paymethod[<?php echo $oid; ?>]" value="Debit / Credit card"> Debit / Credit card</label>&nbsp;
	    				<label><input type="radio" name="paymethod[<?php echo $oid; ?>]" value="UPI"> UPI (scan QR)</label>
	    			</td>
	    		</tr>
	    	<?php } ?>
	    	</tbody>
	    </table>
	    <?php if ($count > 0) { ?>
	    	<p><strong>Grand Total: Rs. <?php echo $grand; ?></strong></p>
	    	<input type="submit" value="Place Order" name="submit" class="btn btn-primary">
	    <?php } else { ?>
	    	<p>No pending products to pay for. <a href="index.php">Continue shopping</a></p>
	    <?php } ?>
	    </form>		
		</div>
		<!-- panel-body  -->

	</div><!-- row -->
</div>
<!-- checkout-step-01  -->
					  
					  	
					</div><!-- /.checkout-steps -->
				</div>
			</div><!-- /.row -->
		</div><!-- /.checkout-box -->
		<!-- ============================================== BRANDS CAROUSEL ============================================== -->
<?php echo include('includes/brands-slider.php');?>
<!-- ============================================== BRANDS CAROUSEL : END ============================================== -->	</div><!-- /.container -->
</div><!-- /.body-content -->
<?php include('includes/footer.php');?>
	<script src="assets/js/jquery-1.11.1.min.js"></script>
	
	<script src="assets/js/bootstrap.min.js"></script>
	
	<script src="assets/js/bootstrap-hover-dropdown.min.js"></script>
	<script src="assets/js/owl.carousel.min.js"></script>
	
	<script src="assets/js/echo.min.js"></script>
	<script src="assets/js/jquery.easing-1.3.min.js"></script>
	<script src="assets/js/bootstrap-slider.min.js"></script>
    <script src="assets/js/jquery.rateit.min.js"></script>
    <script type="text/javascript" src="assets/js/lightbox.min.js"></script>
    <script src="assets/js/bootstrap-select.min.js"></script>
    <script src="assets/js/wow.min.js"></script>
	<script src="assets/js/scripts.js"></script>

	<!-- For demo purposes – can be removed on production -->
	
	<script src="switchstylesheet/switchstylesheet.js"></script>
	
	<script>
		$(document).ready(function(){ 
			$(".changecolor").switchstylesheet( { seperator:"color"} );
			$('.show-theme-options').click(function(){
				$(this).parent().toggleClass('open');
				return false;
			});
		});

		$(window).bind("load", function() {
		   $('.show-theme-options').delay(2000).trigger('click');
		});
	</script>
	<!-- For demo purposes – can be removed on production : End -->

	

</body>
</html>
<?php } ?>