<html>
	<head>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.1/jquery.min.js"></script>
		<title>
		Purplle User details Page
		</title>
		
	</head>
	<body>
	
	<div>
	<h3 style="text-align:center;color:purple;">Welcome to purplle.com please sign In</h1>
	<div id="signin-div" style="color:purple;background-color:grey; width:300px;">
	<div style="width:100px;">Email : </div><div style="width:100px;float:left;"><input id="email" type="text" /></div></br>
	<div style="width:100px;">Password : </div><div style="width:100px;float:left;"><input id="password" type="password" /></div></br>
	<div style="margin-top:20px; margin-left:90px">
	<input  type="button" onclick="signin()" id="btn-signin"  value="Sign In"/>
	</div>
	</div>
	
	<div style="margin-top:10px;width:100px;heigth:20px;background-color:green;" onclick="getcart(<?php echo $cartid ?>)" ><span id="item-count"><?php echo "cart item count ".$cartitemcount?></span> and cartid is <?php echo $cartid ?></div>

	
	<div style="color:purple;background-color:grey; width:300px;"><span id="name"></span></div> <div style="margin-left:20px;color:red;" onclick="logout()" id="logout">Logout</div>
    <?php foreach($cart as $d) { ?>
	<table>
	<tr>
    <td style="heigth:50px;width:200px;background-color:blue;"><?php echo $d->name; ?></td> 
    <td style="heigth:50px;width:200px;background-color:red;"><?php echo $d->desc; ?></td>
    <td style="heigth:50px;width:200px;background-color:yellow;"><?php echo $d->price; ?></td>
	<td><a onclick="addtocart(<?php echo $d->id; ?>)">Add to cart</a></td>
	</tr></br>
	</table>
	
<?php } ?>
<div style="heigth:50px;width:200px;color:blue;margin-top:20px;" onclick="showcart()">show cart</div>
	</div>
	</div>
	</body>
</html>
<script type="text/javascript">
var userId = "0";
var isUserlogedIn = false;

$(document).ready(function() {
	if(window.location.hash.indexOf('userid')>0){
		bindfromhash();
	}
  $('#logout').hide();
});

function bindfromhash(){
	var userid = window.location.hash.split('=')[1];
	$.ajax({
		url : 'getuserinfo',
		type :'GET',
	data :{'userid':userid},
		success:function(result){
			isUserlogedIn =true;
			$('#signin-div').hide();
			$('#name').show();
			$('#name').text("hello "+result);
			$('#logout').show();
		}
		
	});
}

function addtocart(id){
	var itemid = id;
	if(window.location.hash.indexOf('userid')>0){
		var userid = window.location.hash.split('=')[1];
		userId = userid;
	}
	$.ajax({
		url : 'addtocart',
		type :'GET',
	data :{'itemid':itemid,'quantity':"1",'userid':userId,'islogedin':isUserlogedIn},
		success:function(result){
			if(result){
				//alert("added to cart");
				$("#item-count").text("cart item count  "+ result);
			}else{
				alert("unsuccessfull");
			}
		}
		
	});
}

function showcart(){
	if(window.location.hash.indexOf('userid')>0){
		var userid = window.location.hash.split('=')[1];
		userId = userid;
	}
	$.ajax({
		url : 'getcart',
		type :'GET',
		data : {'userid':userId,'islogedin':isUserlogedIn},
		success:function(result){
			window.location.href='/cart/shoping/getcart?userid='+userId+"&islogedin="+isUserlogedIn
		}
		
	});
}

function signin(){
	var email = $("#email").val();
	var password = $("#password").val();
	var url = document.location.href;
	$.ajax({
		url : 'signin',
		type :'GET',
	data :{'email':email,'password':password},
		success:function(result){
			isUserlogedIn =true;
			userId = result;
			$('#signin-div').hide();
			$('#name').show();
			$('#name').text("hello "+email);
			$('#logout').show();
			window.location.hash = "userid="+result;
		}
		
	});
	
}

function logout(){
	$.ajax({
		url :'signout',
		type :'GET',
		success:function(result){
			if(result){
				isUserlogedIn =false;
			userId = "0";
				window.location.href= "/cart/shoping/products";
			}else{
				alert("unsuccessfull");
			}
		}
		
	});
}

function getcart(cartid)
{	
	$.ajax({
		url : 'getcartbyid',
		type :'GET',
		data : {'cartid':cartid},
		success:function(result){
			window.location.href="/cart/shoping/getcartbyid?cartid="+cartid
		}
		
	});
}
</script>