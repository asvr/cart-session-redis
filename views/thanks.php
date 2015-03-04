<html>
	<head>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
		<title>
		Purplle thanks Page
		</title>
		
	</head>
	<body>
	<h3 style="text-align:center;color:purple;">Thanks for signing up to view your prfile</h1>
	<div style="text-align:center;color:purple;background-color:grey;">
	<input/ type="button" value="Click here" onclick="showprofile()">
	</body>
</html>
<script>
function showprofile(){
	var result = document.location.href.split('/')[6];
	window.location.href= "/code/purplle/userdetails/"+result;
}
</script>