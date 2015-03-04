<?php
class Shoping extends CI_Controller
{	
	public function __construct()
	{
	 parent:: __construct();
	 $this->load->library('redis');
	 $this->load->library('session');
	 $this->load->model('Productsmodel','',TRUE);
	 //log_msg('info', "loading redis");
     //$this->load->library('redis', array('connection_group' => 'default'), 'redis_default');
	 header("cache-Control: no-store, no-cache, must-revalidate");
	 header("cache-Control: post-check=0, pre-check=0", false);
	 header("Pragma: no-cache");
	 header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
	}
	
	public function products()
	{
		 // check connection to Redis
		 //echo " in products controller";
			$this->load->model('productsmodel');
			$data = $this->productsmodel->getall();
			$this->load->view('productsview',$data);
		
		 $this->redis->command('PING');
		//echo parse_url("http://foo?bar#fizzbuzz",PHP_URL_FRAGMENT);
		
	}
	
	public function addtocart()
	{
		$this->load->model('productsmodel');
		$data['user'] = $this->productsmodel->addtocart();
	}
	
	public function getcart()
	{
		$this->load->helper('url');
		$this->load->model('productsmodel');
		$data['user'] = $this->productsmodel->getcart();
		//redirect('shoping/showcart',$data);
		$this->load->view('cart',$data);
	}
	
	public function showcart($data)
	{
		$this->load->view('cart',$data);
	}
	
	public function getuserinfo()
	{
		$this->load->model('productsmodel');
		$data = $this->productsmodel->getuserinfo();
		
		
		echo $data;
		exit;
	}
	
	public function updatecart()
	{
		$this->load->model('productsmodel');
		$data['user'] = $this->productsmodel->updatecart($userid);
		
		//$this->load->view('cart',$data);
	}
	
	public function signin()
	{
		$this->load->model('productsmodel');
		$data = $this->productsmodel->signin();
		if($data){
			echo $data;
		}
		else
		{
			echo "0";
		}
		
	}
	
	public function signout()
	 {
		 $this->session->unset_userdata('cartid');
		 $this->session->sess_destroy();
		 echo TRUE;
		 exit;
	}
	
	public function getcartid()
	{
		$this->load->model('productsmodel');
		$data['user'] = $this->productsmodel->getcartid();
		$this->load->view('cart',$data);
	}
	
	public function getcartbyid()
	{
		$this->load->helper('url');
		$this->load->model('productsmodel');
		$data['user'] = $this->productsmodel->getcartbyid();
		//redirect('shoping/showcart',$data);
		$this->load->view('cart',$data);
	}
	
	
	
	
	
 
	function index()
	{
		if ($this->Productsmodel->checkDB()){ // check connection to Redis
			$fields = $this->Productsmodel->getFields();
			if ($fields['orange_click'] == ''){// no fields created so create
				$this->Productsmodel->resetDB();
				$fields = $this->Productsmodel->getFields();
			}
			$data = array(
				'fields' => $fields
			);
			$rand = rand(0,9);
			if ($rand < 1){ // choose random button
				$col_rand = rand(1,3);
				if ($col_rand == '1') $method = 'orange';
				if ($col_rand == '2') $method = 'green';
				if ($col_rand == '3') $method = 'white';
				$data['method_val'] = $method;
				$data['method'] = 'Random: '.ucfirst($data['method_val']);
			} else { // calculate best button to show
				$percents = array();
				$percents['orange'] = $fields['orange_click']/$fields['orange_show']*100;
				$percents['green'] = $fields['green_click']/$fields['green_show']*100;
				$percents['white'] = $fields['white_click']/$fields['white_show']*100;
				arsort($percents); // order array with height percent first
				$data['method_val'] = key($percents);
				$data['method'] = 'Best Choice: '.ucfirst($data['method_val']);
			}
			$this->Productsmodel->increaseShow($data['method_val']);
			$this->load->view('welcome', $data);
		} else {
			echo 'Your Redis Server does not appear to be switched on.';
		}
	}
	
	
}
?>
