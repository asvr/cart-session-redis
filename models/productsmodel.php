<?php
class Productsmodel extends CI_Model
{
	public function __construct()
	{
	 parent:: __construct();
	}

	public function getall()
	{	
		$cartquery = $this->getcartid();
		$query = $this->db->query("SELECT * FROM items");
		$data =array(
		'cartid' => $cartquery->row('cid'),
		'cartitemcount' => $cartquery->row('cartitemcount'),
		'cart' => $query->result()
		);
		
		if ($query->num_rows > 0)
		{
			return $data;
		}else{
			return FALSE;
		}
	}	

	public function addtocart()
	{
		if($this->session->userdata("cartid")){
			$sessionId =$this->session->userdata("cartid");
		}
		else
		{
			$session_data= $this->session->all_userdata();
			$cartid = $session_data['session_id'];
			$this->session->set_userdata("cartid",$cartid);
			$sessionId = $this->session->userdata("cartid");
		}
		
		$itemid =$this->input->get('itemid');
		$userid =$this->input->get('userid');
		$islogedin =$this->input->get('islogedin');
		
		if($islogedin == "true")
		{
			$itemcountquery = $this->db->query("SELECT count(id) as count FROM cart WHERE (custId='$userid')");
			$itemcount = $itemcountquery->row('count');
			$query = $this->db->query("SELECT quantity FROM cart WHERE (custId='$userid') AND itemid='$itemid'");
		}
		else
		{
			$itemcountquery = $this->db->query("SELECT count(id) as count FROM cart WHERE (sessionId='$sessionId')");
			$itemcount = $itemcountquery->row('count');
			$query = $this->db->query("SELECT quantity FROM cart WHERE (sessionId='$sessionId') AND itemid='$itemid'");
			
		}
		
		if($query->num_rows > 0)
		{	
			$existingquantity = $query->row('quantity');
			
			$quantity = $existingquantity+1;
			
			$this->db->query("UPDATE cart set quantity = '$quantity' WHERE (sessionId='$sessionId' OR custId='$userid') AND itemid='$itemid'");
			$cartid = $this->db->insert_id();
			
			echo $itemcount;
		}
		else
		{
			//cheking for existing cart in table
			$cartquery = $this->db->query("SELECT gc.cid as cid FROM  getcart gc inner join cart c ON c.id = gc.cartid  WHERE (sessionId='$sessionId' OR custId='$userid')");
			
			if($cartquery->num_rows > 0)
			{
				$cid = $cartquery->row('cid');
				$data =array(
				'cid' => $cid,
				'cartid'=> "0"
				);
				
			}
			else
			{
				$cid="0";
				$data =array(
				'cid' => $cid,
				'cartid'=> "0"
				);
			}
			
			
			$this->db->insert('getcart',$data);
			$getcartid = $this->db->insert_id();
			 
			$data = array(
			'sessionId' => $sessionId,
			'itemId' => $this->input->get('itemid'),
			'quantity' => $this->input->get('quantity'),
			'custId'=> $userid
			);
			
			$this->db->insert('cart',$data);
			$cartid = $this->db->insert_id();
			
			if($cid =="0"){
				$query = $this->db->query("UPDATE getcart set cid='$getcartid',cartid='$cartid' WHERE id='$getcartid'");
			}
			else
			{
				$query = $this->db->query("UPDATE getcart set cid='$cid',cartid='$cartid' WHERE id='$getcartid'");
			}
			
			echo $itemcount+1;
		}
		
	}

	public function getcart()
	{	
		$userid = $this->input->get('userid');
		$islogedin =$this->input->get('islogedin');
		
		
		if($this->session->userdata("cartid")){
			$sessionId =$this->session->userdata("cartid");
		}
		else
		{
			$session_data= $this->session->all_userdata();
			$cartid = $session_data['session_id'];
			$this->session->set_userdata("cartid",$cartid);
			$sessionId = $this->session->userdata("cartid");
		}
		if($islogedin =="true")
		{
			$query = $this->db->query("SELECT * FROM cart c inner join items i on c.itemid = i.id WHERE (custId='$userid' OR sessionId='$sessionId')");
		}
		else
		{
			$query = $this->db->query("SELECT * FROM cart c inner join items i on c.itemid = i.id WHERE (sessionId='$sessionId')");
		}	
		
		
		if ($query->num_rows > 0)
		{
			return $query->result();
		}
		else
		{
			return FALSE;
		}
	}

	public function getcartid()
	{
		if($this->session->userdata("cartid"))
		{
			$sessionId =$this->session->userdata("cartid");
		}
		else
		{
			$session_data= $this->session->all_userdata();
			$cartid = $session_data['session_id'];
			$this->session->set_userdata("cartid",$cartid);
			$sessionId = $this->session->userdata("cartid");
		}
		
		$cartquery = $this->db->query("SELECT distinct gc.cid AS cid,count(c.id) as cartitemcount from getcart gc inner join cart c on c.id =gc.cartid WHERE sessionId='$sessionId'");

		return $cartquery;
	}

	public function signin(){
		$email = $this->input->get('email');
		$password =$this->input->get('password');

		$query = $this->db->query("SELECT id FROM user where email='$email' AND password='$password'");
		if ($query->num_rows == 1)
		{
			$userid =$query->row('id');
			
			$this->updatecart($userid);
			
			return $userid;
		}
		else{
			return FALSE;
		}
	}	

	public function getuserinfo()
	{
		$userid = $this->input->get('userid');
		$query = $this->db->query("SELECT * FROM user WHERE id ='$userid'");
		if ($query->num_rows > 0)
		{
			return $query->row('email');
		}else{
			return FALSE;
		}
	}

	public function updatecart($userid)
	{
		if($this->session->userdata("cartid"))
		{
			$sessionId =$this->session->userdata("cartid");
		}
		else
		{
			$session_data= $this->session->all_userdata();
			$cartid = $session_data['session_id'];
			$this->session->set_userdata("cartid",$cartid);
			$sessionId = $this->session->userdata("cartid");
		}
		
		$this->db->query("UPDATE cart set custId = '$userid' WHERE sessionId='$sessionId'");
		
	}

	public function getcartbyid()
	{
		$cartid = $this->input->get('cartid');
		$query = $this->db->query("SELECT * FROM getcart gc INNER JOIN cart c ON gc.cartid = c.id INNER JOIN items i ON i.id = c.itemid WHERE gc.cid ='$cartid'");
		
		if ($query->num_rows > 0)
		{
			return $query->result();
		}else{
			return FALSE;
		}
	}

}

?>