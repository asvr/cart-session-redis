<?php
class Signinmodel extends CI_Model{
	public function __construct()
	{
	 parent:: __construct();
	}

public function signin(){
	$name = $this->input->get('name');
	$password =$this->input->get('password');
	
	$query = $this->db->query("SELECT id FROM user where email='$name' AND password='$password'");
	if ($query->num_rows == 1)
		
        {
            return $query->row('id');
        }else{
			return FALSE;
		}
}	

public function userdata($id){
	
	$query = $this->db->query("SELECT * FROM user where id='$id'");
	if ($query->num_rows == 1)
		
        {
            return $query->result();
        }else{
			return FALSE;
		}
}
}
?>