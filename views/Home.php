<html>
	<head>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
		<title>
		Purplle Home Page
		</title>
		
	</head>
	<body>
	<h3 style="text-align:center;color:purple;">Welcome to purplle.com please sign In</h1>
	<div style="text-align:center;color:purple;background-color:grey;">
	UserName : <input id="userName" type="text" /></br>
	Password : <input id="passWord" style="margin-top:20px;" type="password" /></br>
	<div style="margin-top:20px; text-align:center;margin-left:90px">
	<input  type="button" onclick="signIn()" id="btn-signin"  value="Sign In"/>
	<input  type="button" onclick="signUp()" value="New user"/>
	</div>
	</div>
	</body>
</html>
<script type="text/javascript">

function signIn(){
	var name = $("#userName").val();
	var passWord = $("#passWord").val();
	
	$.ajax({
		url : 'signin',
		type :'GET',
	data :{'name':name,'password':passWord},
		success:function(result){
			if(result){
				window.location.href= "/code/purplle/userdetails/"+result;
			}else{
				alert("unsuccessfull");
			}
		}
		
	});
	
}

function signUp(){
	$.ajax({
		url : 'signup',
		type :'Get',
		success:function(result){
		if(result){
			window.location.href= "/code/purplle/signup";
		}
		}
		
	});
	
}
</script>