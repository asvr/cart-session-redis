<html>
	<head>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
		<title>
		Purplle sign up Page
		</title>
		
	</head>
	<body>
	<h3 style="">Welcome to purplle.com Fill your Details</h1>
	<div style="color:purple;background-color:grey;width:600px">
	<div style="width:100px;float:left;"> Name :</div> <div style="width:100px;"><input id="name" type="text" /></div></br>
	<div style="width:100px;float:left;">Email :</div> <div style="width:100px;"><input id="email" type="text" /></div></br>
	<div style="width:100px;float:left;">Mobile :</div> <div style="width:100px;"><input id="mobile" type="text" /></div></br>
	
	<div style="width:100px;float:left;">Password :</div> <div style="width:100px;"><input id="password"  type="password" /></div></br>
	<div style="width:100px;float:left;">Confirm Password :</div style="width:100px;"> <div><input id="re-password" type="password" /></div></br>
	<div style="margin-top:20px; margin-left:100px">
	<div id="spnError" style="float:left;color:red;margin-top:10px;margin-below:10px;"></div>
	<input  type="button" id="btn-save" onclick="save()" value="Save"/>
	
	</div>
	
	</div>
	</body>
</html>

<script type="text/javascript">

function save(){
	var name = $("#name").val();
	var email = $("#email").val();
	var mobile = $("#mobile").val();
	var password = $("#password").val();
	var re_password = $("#re-password").val();
	if(isvalid(name,email,mobile,password,re_password)){
	$.ajax({
		url : 'save',
		type :'GET',
		data :{'name':name,'email':email,'mobile':mobile,'password':password},
		success:function(result){
			if(result){
				window.location.href= "/code/purplle/thanks/"+result;
			}else{
				alert("unsuccessfull");
			}
		}
		
	});
	}
}

function update(){
	var name = $("#name").val();
	var email = $("#email").val();
	var mobile = $("#mobile").val();
	var password = $("#password").val();
	var re_password = $("#re-password").val();
	$.ajax({
		url : 'update',
		type :'Get',
		data :{'name':name,'email':email,'mobile':mobile,'password':password,'re-password':re_password},
		success:function(result){
			if(result){
				alert("successfull");
			}else{
				alert("unsuccessfull");
			}
		}
		
	});
	
}

function isvalid(name,email,mobile,password,re_password){
	var valid = true;
	var errMsg = "";
	if(name == "" || name == "undefined"){
		valid = false;
		errMsg ="please enter name";
	}else if(email == "" || email == 'undefined'){
		valid = false;
		errMsg ="please enter email";
	}else if(mobile == "" ||mobile == 'undefined'){
		valid = false;
		errMsg ="please enter valid mobile";
	}else if(password == ""){
		valid = false;
		errMsg ="please enter password";
	}
	else if( password != re_password){
		valid = false;
		errMsg ="password doesn't matched";
	}
	
	if(valid == false){
		$('#spnError').text(errMsg);
	}
	return valid;
}
</script>