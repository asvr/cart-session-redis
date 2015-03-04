<?php
class signUpModel extends CI_Model{

public function save(){
	$data = array(
	'name' => $this->input->get('name'),
	'email' => $this->input->get('email'),
	'mobile' => $this->input->get('mobile'),
	'password' => $this->input->get('password')
	);
	
	 $this->db->insert('user',$data);
	 
	 return $this->db->insert_id();
}	
public function update($id){
	$data = array(
	'name' => $this->input->get('name'),
	'email' => $this->input->get('email'),
	'mobile' => $this->input->get('mobile'),
	'password' => $this->input->get('password')
	);
	
	 $this->db->query("UPDATE user set name='$name',email='$email',mobile='$mobile',password='$password' WHERE id=$id");
	 
	 return $this->db->insert_id();
}	
}
?>