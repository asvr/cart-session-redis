<?php
 require 'h2o/h2o.php';
class Purplle extends CI_Controller
{	
	public function __construct()
	{
	 parent:: __construct();
	 $this->load->library('session');
	 $this->load->helper('url');
	 header("cache-Control: no-store, no-cache, must-revalidate");
	 header("cache-Control: post-check=0, pre-check=0", false);
	 header("Pragma: no-cache");
	 header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	}
	public function home()
	{	
		if($this->session->userdata("loged_in"))
		{
			$id = $this->session->userdata("loged_in");
			redirect("purplle/userdetails/".$id);
		}
		else
		{
			$this->load->view('Home');
		}
	}
	public function signin()
	{
		$this->load->model('Signinmodel');
		$data = $this->Signinmodel->signin();
		if($data){
			$this->session->set_userdata("loged_in",$data);
			echo $data;
		}
		else
		{
			echo "0";
		}
		
	}
	public function signup()
	{
		$this->load->view('SignUp');
	}
	
	public function userdetails($id)
	{
		if($this->session->userdata("loged_in"))
		{
			$this->load->model('Signinmodel');
			$data['user'] = $this->Signinmodel->userdata($id);
			//$this->load->view('userDetails',$data); //normal php binding
			$h2o = new h2o('templates/userdetails.html'); //django binding
			echo $h2o->render(compact('data'));
		}
		else
		{
			redirect('purplle/home');
		}
	}
	
	public function thanks()
	{
		$this->load->view('thanks');
	}
	public function save()
	{
		$this->load->model('SignUpModel');
		$data = $this->SignUpModel->save();
		if($data){
			$this->session->set_userdata("loged_in",$data);
			echo $data;
			exit;
		}
		//$this->load->view('userDetails',$data);
	}
	// public function update($id)
	// {
		// $this->load->view('SignUpModel');
		// $data['query'] = $this->SignUpModel->update($id);
		// $this->load->view('userDetails',$data);
	// }
	
	public function signout()
	 {
		 $this->session->unset_userdata('loged_in');
		 $this->session->sess_destroy();
		 echo TRUE;
		 exit;
	}
}
?>
