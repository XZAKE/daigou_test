<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class My extends Taobao_Controller {

	function __construct()
	{
		parent::__construct();
		if( ! $GLOBALS['member'] && $this->uri->rsegment(2) != 'cart')
		{
			redirect('member/login');
		}
	}

	function favorites()
	{
		$this->_set_title('Favorites');
		$this->load->model('taobao/taobao_favorite_mdl');
		$page = $this->input->get('page') ? $this->input->get('page') : 1;
		$offset = ($page == 1 ? 0 : ($page-1) * 12 + 1); 
		$data = $this->taobao_favorite_mdl->get_favorites($GLOBALS['member']->uid, 12, $offset);
		$this->_template('member/favorite', $data);
	}
	
	function cart()
	{
		$this->_set_title('Shopping Cart');
		$this->load->model('taobao/taobao_item_mdl');
		$this->load->model('taobao/taobao_cart_mdl');
		$this->load->model('taobao/taobao_logistic_mdl');
		$data['checkout'] = $this->taobao_cart_mdl->checkout();
		$this->_template('member/cart',$data);
	}
	
	function _cart_post()
	{
		$data = array();
		$ids = $this->input->post('id');
		$qtys = $this->input->post('qty');
		foreach($ids as $key=>$v)
		{
			$data[] = array('rowid'=>$v,'qty'=>$qtys[$key]);
		}
		if($data)
		{
			$this->ncart->update($data);	
		}
		redirect('my/cart');
	}
	
	function logistic($action = 'list')
	{
		$this->_set_title('Address');
		$this->load->model('taobao/taobao_logistic_mdl');
		$data['action'] = $action ;
		if($action == 'list')
		{
			$data['logistics'] = $this->taobao_logistic_mdl->get_logistic_by_uid($GLOBALS['member']->uid);	
		}
		elseif($action == 'edit')
		{
			$id = $this->uri->segment(4,0);
			$data['logistic'] = $this->taobao_logistic_mdl->get_logistic_by_id($id);
		}
		elseif($action == 'del')
		{
			$id = $this->uri->segment(4,0);
			$this->taobao_logistic_mdl->delete_logistic($id);
			redirect('my/logistic');	
		}
		include (DILICMS_SHARE_PATH . 'settings/taobao/country.php');
		$data['country'] = $geo_country;
		$this->_template('member/address',$data);
	}
	
	function _logistic_post()
	{
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<span class="colorA10">', '</span>');
		$this->form_validation->set_rules('logistics_name', '收货人姓名', 'trim|required');
		$this->form_validation->set_rules('logistics_address', '收货人地址', 'trim|required');
		$this->form_validation->set_rules('logistics_phone', '收货人电话', 'trim|required');
		if ($this->form_validation->run() == FALSE)
		{
			header("content-type:text/html; charset=utf-8");
			echo '<script language="javascript">alert("please fill all the blanks!");history.back(-1);</script>';
		}
		else
		{
			$this->load->model('taobao/taobao_logistic_mdl');
			if($id = $this->input->post('id'))
			{
				$this->taobao_logistic_mdl->update_logistic();
			}
			else
			{
				$this->taobao_logistic_mdl->add_logistic();
			}
			redirect('my/logistic');
		}
	}
	
	function orders()
	{
		$this->_set_title('Purchase Orders');
		$this->load->model('taobao/taobao_order_mdl');
		$where['uid'] = $GLOBALS['member']->uid;
		$suffix = '';
		$status = $this->input->get('status');
		if ($status !== FALSE AND $status != '')
		{
			$where['status'] = $status;
			$suffix .= '&status='.$status;
		}
		if ($orderid = $this->input->get('orderid'))
		{
			$where['id'] = $orderid;
		}
		$page = $this->input->get('page') ? $this->input->get('page') : 1;
		if ($page == 1)
		{
			$offset = 0;	
		}
		else
		{
			$offset = 10 * ($page - 1) + 1;	
		}
		$data = $this->taobao_order_mdl->get_orders($where, 10, $offset, $suffix);
		$data['current'] = $status;
		$data['status'] = $this->taobao_order_mdl->get_status_code();
		include (DILICMS_SHARE_PATH . 'settings/taobao/country.php');
		$data['country'] = $geo_country;
		$this->_template('member/orders',$data);
	}
	
	function order($order_id = 0)
	{
		$this->load->model('taobao/taobao_order_mdl');
		$data['order'] = $this->taobao_order_mdl->get_full_order_by_id($order_id);
		$data['status'] = $this->taobao_order_mdl->get_status_code();
		if(!$data['order'])
		{
			$this->_message('None existed Order !');
		}
		$this->_set_title('Order:'.$order_id);
		$this->_template('member/order', $data);
	}

	function deliveries()
	{
		$this->_set_title('Delivery Orders');
		$this->load->model('taobao/taobao_order_mdl');
		$this->load->model('taobao/taobao_delivery_mdl');
		$where['uid'] = $GLOBALS['member']->uid;
		$suffix = '';
		$status = $this->input->get('status');
		if ($status !== FALSE AND $status != '')
		{
			$where['status'] = $status;
			$suffix .= '&status='.$status;
		}
		if ($orderid = $this->input->get('orderid'))
		{
			$where['id'] = $orderid;
		}
		$page = $this->input->get('page') ? $this->input->get('page') : 1;
		if ($page == 1)
		{
			$offset = 0;	
		}
		else
		{
			$offset = 10 * ($page - 1) + 1;	
		}
		$data = $this->taobao_delivery_mdl->get_orders($where, 10, $offset, $suffix);
		$data['current'] = $status;
		$data['status'] = $this->taobao_delivery_mdl->get_status_code();
		$this->_template('member/deliveries', $data);
	}
	
	function delivery($order_id = 0)
	{
		$this->load->model('taobao/taobao_order_mdl');
		$this->load->model('taobao/taobao_delivery_mdl');
		$data['order'] = $this->taobao_delivery_mdl->get_full_order_by_id($order_id);
		$data['status'] = $this->taobao_delivery_mdl->get_status_code();
		if(!$data['order'])
		{
			$this->_message('None existed Order !');
		}
		$this->_set_title('Delivery Order:'.$order_id);
		$this->_template('member/delivery', $data);
	}
	
	function inviter()
	{
		$this->_set_title('Recommend');
		$this->load->model('taobao/taobao_inviter_mdl');
		$data['inviter'] = $this->taobao_inviter_mdl->get_inviter_by_uid($GLOBALS['member']->uid);
		if ($data['inviter'])
		{
			$limit = 10;
			$offset = get_page_offset($limit);
			$where = array('uid' => $GLOBALS['member']->uid);
			$data['detail'] = $this->taobao_inviter_mdl->get_cash_records($where, $limit, $offset, '', site_url('my/inviter'));
		}
		$this->_template('member/inviter', $data);
	}
	
	function _inviter_post()
	{
		$data['type'] = $this->input->post('type', TRUE);
		$data['paypal'] = $this->input->post('paypal', TRUE);
		if ( ! $data['type'] || ($data['type'] == 2 && $data['paypal'] == ''))
		{
			redirect('my/inviter');	
		}
		else
		{
			$this->load->model('taobao/taobao_inviter_mdl');
			$this->taobao_inviter_mdl->add_inviter();
			redirect('my/inviter');
		}
	}
	
	function history($type = "credit")
	{
		$where = array();
		$where['uid'] = $GLOBALS['member']->uid;
		if ($type == 'cash')
		{
			$this->_set_title('Promotion Cash Records');
			$where['iuid >'] = 0;	
		}
		else
		{
			$this->_set_title('Credit Records');	
		}
		$limit = 10;
		$offset = get_page_offset($limit);
		$this->load->model('taobao/taobao_inviter_mdl');
		$data = $this->taobao_inviter_mdl->get_history($where, $limit, $offset, '', 'my/history/'.$type);
		$this->_template('member/history', $data);
	}
	

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */