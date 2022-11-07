<?php
class ControllerExtensionShippingApiship extends Controller {

	public function __construct($params) {
		parent::__construct($params);

		$this->shipping_apiship_mode = $this->config->get('shipping_apiship_mode');

	}

	public function set_point() {
		$this->load->model('extension/shipping/apiship');
		return $this->model_extension_shipping_apiship->set_point(); 
	}

	public function get_points() {
		$this->load->model('extension/shipping/apiship');
		return $this->model_extension_shipping_apiship->get_points(); 
	}

	public function export_order() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->export_order(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 
	}

	public function cancel_order() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->cancel_order(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 
	}

	public function import_orders() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->import_orders(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 
	}

	public function get_label() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->get_label(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 

	}

	public function get_waybill() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->get_waybill(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 

	}

	public function get_order_params() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->get_order_params(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results)); 

	}

	public function get_last_tracing_id() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->get_last_tracing_id(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode('x_tracing_id:' . $results)); 

	}

	public function get_point() {
		$this->load->model('extension/shipping/apiship');
		$results = $this->model_extension_shipping_apiship->get_point(); 
		$this->response->addHeader('Content-Type: application/json');		
		$this->response->setOutput(json_encode($results));
	}


}