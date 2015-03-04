<html>
	<head>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
		<title>
		Purplle User details Page
		</title>
		
	</head>
	<body>
	
    <?php if($user){
	foreach($user as $d) { ?>
	<table>
	<tr>
    <td style="heigth:50px;width:200px;background-color:blue;"><?php echo $d->name; ?></td> 
    <td style="heigth:50px;width:200px;background-color:red;"><?php echo $d->desc; ?></td>
    <td style="heigth:50px;width:200px;background-color:yellow;"><?php echo $d->price; ?></td>
    <td style="heigth:50px;width:200px;background-color:grey;"><?php echo $d->quantity; ?>
	<td><a onclick="addtocart(<?php echo $d->id; ?>)">remove from cart</a></td>
	</tr></br>
	</table>
	<?php }}else{ ?>
		<div style="heigth:50px;width:200px;background-color:blue;">currently no item in cart</div> 
	<?php } ?>
	</div>
	</div>
	</body>
</html>
<script type="text/javascript">
// function addtocart(id){
	// var itemid = id;
	// $.ajax({
		// url : 'addtocart',
		// type :'GET',
	// data :{'itemid':itemid,'quantity':"1"},
		// success:function(result){
			// if(result){
				// //window.location.href= "/code/shoping/addtocart";
			// }else{
				// alert("unsuccessfull");
			// }
		// }
		
	// });
// }
</script>