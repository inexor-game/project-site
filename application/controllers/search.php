<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends CI_Controller {
	function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
		$this->load->library('ion_auth');
		$this->load->helper('url');
		$this->load->config('search', FALSE);
		$this->load->helper('template');
	}

	function index() 
	{
		$target_error = false;
		$data = array();
		$searchtarget = $this->config->item('search');
		$this->form_validation->set_rules('search', 'Search', 'required');
		if(count($searchtarget) == 1) {
			$searchtarget = $searchtarget[key($searchtarget)][0];
		} elseif(!isset($searchtarget[$this->input->post('target')])) {
			$target_error = true;
		} else {
			$searchtarget = $this->input->post('target');
		}
		if($this->form_validation->run() == FALSE || $target_error)
		{
			$error = array();
			$validation_error = validation_errors();
			if($validation_error) {
				$error[] = $validation_error;
			}
			if($target_error) {
				$error[] = 'Please select a search-target.';
			}
			$data['validation_message'] = $error;
		} else {
			redirect($searchtarget . '/' . urlencode($this->input->post('search')));
		}

		$this->_render_page('search/search', $data);
	}

	function api($object, $search, $start = 0, $limit = 30)
	{
		if(strlen($search) < $this->config->item('min_chars'))
		{
			$results = array('error' => array('message' => 'You have to enter minimum ' . $this->config->item('min_chars') . ' characters to get any results!', 'reason' => 'min_letters'));
		}
		else
		{
			if($start != isint($start)) $start = 0;
			if($limit != isint($limit)) $limit = 30;
			if(1 > $limit || $limit > $this->config->item('max_results')) $limit = 30;
			$search = urldecode($search);
			if($object == 'user')
			{
				$results = $this->_search_user($search, $start, $limit);
			}
			$results['results'] = count($results);
		}
		$this->output->set_content_type('application/json')->set_output(json_encode($results));
	}

	function _search_user($username, $start = 0, $limit = 30)
	{
		$this->ion_auth->limit($limit, $start);
		$this->ion_auth->order_by('username', 'asc');
		$this->ion_auth->like('username', $username);
		$this->ion_auth->like('first_name', $username);
		$this->ion_auth->like('last_name', $username);
		$users = $this->ion_auth->users()->result();
		$return = array();
		foreach($users as $u) 
		{
			$return[] = array(
				'id' => $u->id,
				'username' => $u->username,
				'first_name' => $u->first_name,
				'last_name' => $u->last_name,
				'created_on' => $u->created_on,
				'about' => $u->about,
				'images' => array(
					'avatar' => iimage($u->id, 1),
					'background' => iimage($u->id, 2)
				)
			);
		}
		return $return;
	}

	function _render_page($view, $data=null, $render=false)
	{

		$view_html = $this->load->view($view, $data, $render);

		if (!$render) return $view_html;
	}
}