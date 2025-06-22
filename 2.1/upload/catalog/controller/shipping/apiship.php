<?php
class ControllerShippingApiship extends Controller {

	//public function __construct($params) {
	//	parent::__construct($params);

	//	$this->shipping_apiship_mode = $this->config->get('shipping_apiship_mode');

	//}

	private function check_key() {
		$this->load->language('extension/shipping/apiship');
		$results = 	['status' => 'error', 'error' => 'Invalid key'];
		if (isset($this->request->get['key'])) 
		{
			if ($this->request->get['key'] == $this->config->get('shipping_apiship_cron_key'))
			{
				$results['status'] = 'ok';				
			}
		} 
		return $results;
	}


	public function set_point() {
		$this->load->model('shipping/apiship');
		return $this->model_shipping_apiship->set_point(); 
	}

	public function get_points() {
		$this->load->model('shipping/apiship');
		return $this->model_shipping_apiship->get_points(); 
	}

	public function export_order() {
		$results = $this->check_key();
		if ($results['status'] == 'ok') 
		{
			$this->load->model('shipping/apiship');
			$results = $this->model_shipping_apiship->export_order(); 
		}

		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 
	}

	public function cancel_order() {
		$results = $this->check_key();
		if ($results['status'] == 'ok') 
		{
			$this->load->model('shipping/apiship');
			$results = $this->model_shipping_apiship->cancel_order(); 
		}
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 
	}

	public function import_orders() {
		$results = $this->check_key();
		if ($results['status'] == 'ok') 
		{
			$this->load->model('shipping/apiship');
			$results = $this->model_shipping_apiship->import_orders(); 
		}

		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 
	}

	public function export_orders() {
		$results = $this->check_key();
		if ($results['status'] == 'ok') 
		{
			$this->load->model('shipping/apiship');
			$results = $this->model_shipping_apiship->export_orders(); 
		}

		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 
	}

	public function get_label() {
		$this->load->model('shipping/apiship');
		$results = $this->model_shipping_apiship->get_label(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 

	}

	public function get_waybill() {
		$this->load->model('shipping/apiship');
		$results = $this->model_shipping_apiship->get_waybill(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 

	}

	public function get_order_params() {
		$this->load->model('shipping/apiship');
		$results = $this->model_shipping_apiship->get_order_params(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 

	}

	public function get_delivery_cost_original() {
		$this->load->model('shipping/apiship');
		$results = $this->model_shipping_apiship->get_delivery_cost_original(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 

	}

	public function get_last_tracing_id() {
		$this->load->model('shipping/apiship');
		$results = $this->model_shipping_apiship->get_last_tracing_id(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode('x_tracing_id:' . $results)); 

	}

	public function get_point() {
		$this->load->model('shipping/apiship');
		$results = $this->model_shipping_apiship->get_point(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results));
	}


}