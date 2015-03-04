<html>
	<head>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
		<title>
		Purplle User details Page
		</title>
		
	</head>
	<body>
	<h3>Welcome to purplle.com your details are</h1><div><input type="button" value="logout" onclick="logout_new()"/></div>
	
    <?php foreach($user as $d) { ?>
    <td><?php echo $d->name; ?></td></br>
    <td><?php echo $d->email; ?></td></br>
    <td><?php echo $d->mobile; ?></td></br>
	
<?php } ?>
	</div>
	</div>
	</body>
</html>
<script type="text/javascript">

function logout_new(){
	$.ajax({
		url :'signout',
		type :'GET',
		success:function(result){
			if(result){
				window.location.href= "/code/purplle/home";
			}else{
				alert("unsuccessfull");
			}
		}
		
	});
}

function update(){
	var result = document.location.href.split('/')[6];
	$.ajax({
		url : 'update',
		type :'Get',
		success:function(result){
			if(result){
				//window.location.href= "/code/purplle/update/"+result;
			}else{
				alert("unsuccessfull");
			}
		}
		
	});
	
}

</script>