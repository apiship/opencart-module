<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-apiship" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
       </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?> </h3>
      </div>
      <div class="panel-body">

      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-apiship" class="form-horizontal">

	<div>
		<img style="border: 0px solid #EEEEEE;" title="apiship" alt="apiship" src="view/image/shipping/apiship.png">
	</div>

          <div class="form-group">
            <label class="col-sm-4 control-label" ></label>
            <div class="col-sm-8">
			<b><?php echo $entry_main_settings; ?></b>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_rub_select"><?php echo $entry_shipping_apiship_rub_select; ?></label>
            <div class="col-sm-8">
              <select name="shipping_apiship_rub_select" id="shipping_apiship_rub_select" class="form-control">
              	<?php foreach($currencies as $currency) { ?>
              	<?php if ($currency['code'] == $shipping_apiship_rub_select) { ?>
              	<option value=<?php echo $currency['code']; ?> selected="selected"><?php echo $currency['code']; ?></option>
              	<?php } else { ?> 
              	<option value=<?php echo $currency['code'];?>><?php echo $currency['code'];?></option>
	        	<?php } ?>
              	<?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_gr_select"><?php echo $entry_shipping_apiship_gr_select; ?></label>
            <div class="col-sm-8">
              <select name="shipping_apiship_gr_select" id="shipping_apiship_gr_select" class="form-control">
                	<?php foreach($weight_classes as $weight_class) { ?>
                	<?php if ($weight_class['weight_class_id'] == $shipping_apiship_gr_select) { ?>
                	<option value="<?php echo $weight_class['weight_class_id']; ?>" selected="selected"><?php echo $weight_class['title']; ?></option>
              	<?php } else { ?> 
                	<option value="<?php echo $weight_class['weight_class_id']; ?>"><?php echo $weight_class['title']; ?></option>
	        	<?php } ?>
              	<?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_cm_select"><?php echo $entry_shipping_apiship_cm_select; ?></label>
            <div class="col-sm-8">
              <select name="shipping_apiship_cm_select" id="shipping_apiship_cm_select" class="form-control">
                	<?php foreach($length_classes as $length_class) { ?>
                	<?php if ($length_class['length_class_id'] == $shipping_apiship_cm_select) { ?>
                	<option value="<?php echo $length_class['length_class_id']; ?>" selected="selected"><?php echo $length_class['title']; ?></option>
              	<?php } else { ?> 
                	<option value="<?php echo $length_class['length_class_id']; ?>"><?php echo $length_class['title']; ?></option>
	        	<?php } ?>
              	<?php } ?>
              	</select>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_token"><?php echo $entry_shipping_apiship_token; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_token" value="<?php echo $shipping_apiship_token; ?>" placeholder="<?php echo $entry_shipping_apiship_token; ?>" id="shipping_apiship_token" class="form-control" />
			<?php if ($error_shipping_apiship_token) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_token; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_title"><?php echo $entry_shipping_apiship_title; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_title" value="<?php echo $shipping_apiship_title; ?>" placeholder="" id="shipping_apiship_title" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_template"><?php echo $entry_shipping_apiship_template; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_template" value="<?php echo $shipping_apiship_template; ?>" placeholder="<?php echo $entry_shipping_apiship_template; ?>" id="shipping_apiship_template" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_custom_code"><?php echo $entry_shipping_apiship_custom_code; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_custom_code" value="<?php echo $shipping_apiship_custom_code; ?>" placeholder="" id="shipping_apiship_custom_code" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_include_fees"><?php echo $entry_shipping_apiship_include_fees; ?></label>
            <div class="col-sm-8">
			<div class="checkbox">
				<label>
		                  <?php if ($shipping_apiship_include_fees) { ?>
		                  <input type="checkbox" name="shipping_apiship_include_fees" id="shipping_apiship_include_fees" value="1" checked="checked" />
		                  <?php } else { ?>
		                  <input type="checkbox" name="shipping_apiship_include_fees" id="shipping_apiship_include_fees" value="1" />
		                  <?php } ?>
      		      </label>
			</div>
		</div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_group_points"><?php echo $entry_shipping_apiship_group_points; ?></label>
            <div class="col-sm-8">
			<div class="checkbox">
				<label>
		                  <?php if ($shipping_apiship_group_points) { ?>
		                  <input type="checkbox" name="shipping_apiship_group_points" id="shipping_apiship_group_points" value="1" checked="checked" />
		                  <?php } else { ?>
		                  <input type="checkbox" name="shipping_apiship_group_points" id="shipping_apiship_group_points" value="1" />
		                  <?php } ?>
      		      </label>
			</div>
		</div>
          </div>


         <div class="form-group">
            <label class="col-sm-4 control-label" ></label>
            <div class="col-sm-8">
			<b><?php echo $entry_sending_address; ?></b>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_sending_country_code"><?php echo $entry_shipping_apiship_sending_country_code; ?></label>
            <div class="col-sm-8">
		<select name="shipping_apiship_sending_country_code" class="form-control">
              	<?php foreach($shipping_apiship_countries as $mode) { ?>
              	<?php if ($mode['code'] == $shipping_apiship_sending_country_code) { ?>
              	<option value="<?php echo $mode['code']; ?>" selected="selected"><?php echo $mode['code_text']; ?></option>
              	<?php } else { ?> 
              	<option value="<?php echo $mode['code']; ?>"><?php echo $mode['code_text']; ?></option>
	        	<?php } ?>
              	<?php } ?>
            </select>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_sending_region"><?php echo $entry_shipping_apiship_sending_region; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_sending_region" value="<?php echo $shipping_apiship_sending_region; ?>" placeholder="<?php echo $entry_shipping_apiship_sending_region; ?>" id="shipping_apiship_sending_region" class="form-control" />
			<?php if ($error_shipping_apiship_sending_region) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_sending_region; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_sending_city"><?php echo $entry_shipping_apiship_sending_city; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_sending_city" value="<?php echo $shipping_apiship_sending_city; ?>" placeholder="<?php echo $entry_shipping_apiship_sending_city; ?>" id="shipping_apiship_sending_city" class="form-control" />
			<?php if ($error_shipping_apiship_sending_city) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_sending_city; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_sending_street"><?php echo $entry_shipping_apiship_sending_street; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_sending_street" value="<?php echo $shipping_apiship_sending_street; ?>" placeholder="<?php echo $entry_shipping_apiship_sending_street; ?>" id="shipping_apiship_sending_street" class="form-control" />
			<?php if ($error_shipping_apiship_sending_street) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_sending_street; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_sending_house"><?php echo $entry_shipping_apiship_sending_house; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_sending_house" value="<?php echo $shipping_apiship_sending_house; ?>" placeholder="<?php echo $entry_shipping_apiship_sending_house; ?>" id="shipping_apiship_sending_house" class="form-control" />
			<?php if ($error_shipping_apiship_sending_house) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_sending_house; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_sending_block"><?php echo $entry_shipping_apiship_sending_block; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_sending_block" value="<?php echo $shipping_apiship_sending_block; ?>" placeholder="<?php echo $entry_shipping_apiship_sending_block; ?>" id="shipping_apiship_sending_block" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_sending_office"><?php echo $entry_shipping_apiship_sending_office; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_sending_office" value="<?php echo $shipping_apiship_sending_office; ?>" placeholder="<?php echo $entry_shipping_apiship_sending_office; ?>" id="shipping_apiship_sending_office" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" ></label>
            <div class="col-sm-8">
			<b><?php echo $entry_contact_details; ?></b>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_contact_organization"><?php echo $entry_shipping_apiship_contact_organization; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_contact_organization" value="<?php echo $shipping_apiship_contact_organization; ?>" placeholder="<?php echo $entry_shipping_apiship_contact_organization; ?>" id="shipping_apiship_contact_organization" class="form-control" />
			<?php if ($error_shipping_apiship_contact_organization) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_contact_organization; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_contact_name"><?php echo $entry_shipping_apiship_contact_name; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_contact_name" value="<?php echo $shipping_apiship_contact_name; ?>" placeholder="<?php echo $entry_shipping_apiship_contact_name; ?>" id="shipping_apiship_contact_name" class="form-control" />
			<?php if ($error_shipping_apiship_contact_name) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_contact_name; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_contact_phone"><?php echo $entry_shipping_apiship_contact_phone; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_contact_phone" value="<?php echo $shipping_apiship_contact_phone; ?>" placeholder="<?php echo $entry_shipping_apiship_contact_phone; ?>" id="shipping_apiship_contact_phone" class="form-control" />
			<?php if ($error_shipping_apiship_contact_phone) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_contact_phone; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_contact_email"><?php echo $entry_shipping_apiship_contact_email; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_contact_email" value="<?php echo $shipping_apiship_contact_email; ?>" placeholder="<?php echo $entry_shipping_apiship_contact_email; ?>" id="shipping_apiship_contact_email" class="form-control" />
			<?php if ($error_shipping_apiship_contact_email) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_contact_email; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" ></label>
            <div class="col-sm-8">
			<b><?php echo $entry_parcel_defaults; ?></b>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_parcel_length"><?php echo $entry_shipping_apiship_parcel_length; ?></label>
            <div class="col-sm-8">
              <input type="number" name="shipping_apiship_parcel_length" value="<?php echo $shipping_apiship_parcel_length; ?>" placeholder="<?php echo $entry_shipping_apiship_parcel_length; ?>" id="shipping_apiship_parcel_length" class="form-control" />
			<?php if ($error_shipping_apiship_parcel_length) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_parcel_length; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_parcel_width"><?php echo $entry_shipping_apiship_parcel_width; ?></label>
            <div class="col-sm-8">
              <input type="number" name="shipping_apiship_parcel_width" value="<?php echo $shipping_apiship_parcel_width; ?>" placeholder="<?php echo $entry_shipping_apiship_parcel_width; ?>" id="shipping_apiship_parcel_width" class="form-control" />
			<?php if ($error_shipping_apiship_parcel_width) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_parcel_width; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_parcel_height"><?php echo $entry_shipping_apiship_parcel_height; ?></label>
            <div class="col-sm-8">
              <input type="number" name="shipping_apiship_parcel_height" value="<?php echo $shipping_apiship_parcel_height; ?>" placeholder="<?php echo $entry_shipping_apiship_parcel_height; ?>" id="shipping_apiship_parcel_height" class="form-control" />
			<?php if ($error_shipping_apiship_parcel_height) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_parcel_height; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group required">
            <label class="col-sm-4 control-label" for="shipping_apiship_parcel_weight"><?php echo $entry_shipping_apiship_parcel_weight; ?></label>
            <div class="col-sm-8">
              <input type="number" name="shipping_apiship_parcel_weight" value="<?php echo $shipping_apiship_parcel_weight; ?>" placeholder="<?php echo $entry_shipping_apiship_parcel_weight; ?>" id="shipping_apiship_parcel_weight" class="form-control" />
			<?php if ($error_shipping_apiship_parcel_weight) { ?>
              		<div class="text-danger"><?php echo $error_shipping_apiship_parcel_weight; ?></div>
              	<?php } ?>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" ></label>
            <div class="col-sm-8">
			<b><?php echo $entry_place_defaults; ?></b>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_place_length"><?php echo $entry_shipping_apiship_place_length; ?></label>
            <div class="col-sm-8">
              <input type="number" name="shipping_apiship_place_length" value="<?php echo $shipping_apiship_place_length; ?>" placeholder="<?php echo $entry_shipping_apiship_place_length; ?>" id="shipping_apiship_place_length" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_place_width"><?php echo $entry_shipping_apiship_place_width; ?></label>
            <div class="col-sm-8">
              <input type="number" name="shipping_apiship_place_width" value="<?php echo $shipping_apiship_place_width; ?>" placeholder="<?php echo $entry_shipping_apiship_place_width; ?>" id="shipping_apiship_place_width" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_place_height"><?php echo $entry_shipping_apiship_place_height; ?></label>
            <div class="col-sm-8">
              <input type="number" name="shipping_apiship_place_height" value="<?php echo $shipping_apiship_place_height; ?>" placeholder="<?php echo $entry_shipping_apiship_place_height; ?>" id="shipping_apiship_place_height" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_place_weight"><?php echo $entry_shipping_apiship_place_weight; ?></label>
            <div class="col-sm-8">
              <input type="number" name="shipping_apiship_place_weight" value="<?php echo $shipping_apiship_place_weight; ?>" placeholder="<?php echo $entry_shipping_apiship_place_weight; ?>" id="shipping_apiship_place_weight" class="form-control" />
            </div>
          </div>


         <div class="form-group">
            <label class="col-sm-4 control-label" ></label>
            <div class="col-sm-8">
			<b><?php echo $entry_providers_points; ?></b>
            </div>
          </div>

	    <?php foreach($shipping_apiship_providers as $provider) { ?>
          <div class="form-group">
		<div class="col-sm-12">    

			<div class="row">

				<label class="col-sm-4 control-label" for="shipping_apiship_provider[<?php echo $provider['key']; ?>][pickup_type]"><?php echo $entry_providers_point; ?> <?php echo $provider['name']; ?></label>
				<div class="checkbox col-sm-1">
					<label>
			                  <?php if (isset($shipping_apiship_provider[$provider['key']]['pickup_type'])) { ?>
			                  <input class="shipping_apiship_provider_checkbox" data-id="<?php echo $provider['key']; ?>" type="checkbox" name="shipping_apiship_provider[<?php echo $provider['key']; ?>][pickup_type]" id="shipping_apiship_provider[<?php echo $provider['key']; ?>][pickup_type]" value="1" checked="checked" />
			                  <?php } else { ?>
			                  <input class="shipping_apiship_provider_checkbox" data-id="<?php echo $provider['key']; ?>" type="checkbox" name="shipping_apiship_provider[<?php echo $provider['key']; ?>][pickup_type]" id="shipping_apiship_provider[<?php echo $provider['key']; ?>][pickup_type]" value="1" />
			                  <?php } ?>
	      		      </label>
				</div>

	            	<div class="col-sm-7">
	              		<select class="shipping_apiship_provider" data-id="<?php echo $provider['key']; ?>" name="shipping_apiship_provider[<?php echo $provider['key']; ?>][id]" id="shipping_apiship_provider_<?php echo $provider['key']; ?>" value="<?php if (isset($shipping_apiship_provider[$provider['key']]['id'])) echo $shipping_apiship_provider[$provider['key']]['id']; else echo ''; ?>" placeholder="" class="form-control">
					<option value="<?php if (isset($shipping_apiship_providers_points[$provider['key']]['id'])) echo $shipping_apiship_providers_points[$provider['key']]['id']; else echo ''; ?>"><?php if (isset($shipping_apiship_providers_points[$provider['key']]['address'])) echo $shipping_apiship_providers_points[$provider['key']]['address']; else echo ''; ?></option>
					</select>
	            	</div>
			</div>
            </div>
	    </div>
	    <?php } ?>

          <div class="form-group">
            <label class="col-sm-4 control-label" ></label>
            <div class="col-sm-8">
			<b><?php echo $entry_events_mapping; ?></b>
            </div>
          </div>

	    <?php foreach($shipping_apiship_integrator_statuses as $status) { ?>
          <div class="form-group">
		<div class="col-sm-12">    

			<div class="row">

				<label class="col-sm-4 control-label" for="shipping_apiship_mapping_status[<?php echo $status['key']; ?>][use]"><?php echo $status['name']; ?></label>
				<div class="checkbox col-sm-1">
					<label>
			                  <?php if (isset($shipping_apiship_mapping_status[$status['key']]['use'])) { ?>
			                  <input class="shipping_apiship_mapping_checkbox_use" data-id="<?php echo $status['key']; ?>" type="checkbox" name="shipping_apiship_mapping_status[<?php echo $status['key']; ?>][use]" id="shipping_apiship_mapping_status[<?php echo $status['key']; ?>][use]" value="1" checked="checked" />
			                  <?php } else { ?>
			                  <input class="shipping_apiship_mapping_checkbox_use" data-id="<?php echo $status['key']; ?>" type="checkbox" name="shipping_apiship_mapping_status[<?php echo $status['key']; ?>][use]" id="shipping_apiship_mapping_status[<?php echo $status['key']; ?>][use]" value="1" />
			                  <?php } ?>
	      		      </label>
				</div>
	            	<div class="col-sm-4">
	              		<select name="shipping_apiship_mapping_status[<?php echo $status['key']; ?>][order_status_id]" data-id="<?php echo $status['key']; ?>" id="shipping_apiship_mapping_use_<?php echo $status['key']; ?>" class="form-control">
					<?php foreach($order_statuses as $order_status) { ?>
		                  <?php if (isset($shipping_apiship_mapping_status[$status['key']]['order_status_id']) && $order_status['order_status_id'] == $shipping_apiship_mapping_status[$status['key']]['order_status_id']) { ?>
		                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
		                  <?php } else { ?>
		                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
		                  <?php } ?>
		                  <?php } ?>
					</select>
	            	</div>

				<div class="checkbox col-sm-2">
					<label><?php echo $entry_events_mapping_notify; ?>
					</label>
				</div>

				<div class="checkbox col-sm-1">
					<label>
			                  <?php if (isset($shipping_apiship_mapping_status[$status['key']]['notify'])) { ?>
			                  <input class="shipping_apiship_mapping_checkbox_notify" data-id="<?php echo $status['key']; ?>" type="checkbox" name="shipping_apiship_mapping_status[<?php echo $status['key']; ?>][notify]" id="shipping_apiship_mapping_notify_<?php echo $status['key']; ?>" value="1" checked="checked" />
			                  <?php } else { ?>
			                  <input class="shipping_apiship_mapping_checkbox_notify" data-id="<?php echo $status['key']; ?>" type="checkbox" name="shipping_apiship_mapping_status[<?php echo $status['key']; ?>][notify]" id="shipping_apiship_mapping_notify_<?php echo $status['key']; ?>" value="1" />
			                  <?php } ?>
	      		      </label>
				</div>

			</div>
            </div>
	    </div>
	    <?php } ?>


          <div class="form-group">
            <label class="col-sm-4 control-label" ></label>
            <div class="col-sm-8">
			<b><?php echo $entry_extra_settings; ?></b>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="input-tax-class"><?php echo $entry_shipping_apiship_tax_class; ?></label>
            <div class="col-sm-8">
              <select name="shipping_apiship_tax_class_id" id="input-tax-class" class="form-control">
                <option value="0"><?php echo $text_none; ?></option>
                	<?php foreach($tax_classes as $tax_class) { ?>
                	<?php if ($tax_class['tax_class_id'] == $shipping_apiship_tax_class_id) { ?>
                	<option value="<?php echo $tax_class['tax_class_id']; ?>" selected="selected"><?php echo $tax_class['title']; ?></option>
              	<?php } else { ?> 
                	<option value="<?php echo $tax_class['tax_class_id']; ?>"><?php echo $tax_class['title']; ?></option>
	        	<?php } ?>
              	<?php } ?>
              </select>
            </div>
          </div>


          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship-geo-zone"><?php echo $entry_shipping_apiship_geo_zone; ?></label>
            <div class="col-sm-8">
            <select name="shipping_apiship_geo_zone_id" id="shipping_apiship-geo-zone" class="form-control">
              <option value="0"><?php echo $text_all_zones; ?></option>
		  	<?php foreach($geo_zones as $geo_zone) { ?>
		  	<?php if ($geo_zone['geo_zone_id'] == $shipping_apiship_geo_zone_id) { ?>
              	<option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
              	<?php } else { ?> 
              	<option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
	        	<?php } ?>
              	<?php } ?>
            </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_icon_show"><?php echo $entry_shipping_apiship_icon_show; ?></label>
            <div class="col-sm-8">
			<div class="checkbox">
				<label>
		                  <?php if ($shipping_apiship_icon_show) { ?>
		                  <input type="checkbox" name="shipping_apiship_icon_show" id="shipping_apiship_icon_show" value="1" checked="checked" />
		                  <?php } else { ?>
		                  <input type="checkbox" name="shipping_apiship_icon_show" id="shipping_apiship_icon_show" value="1" />
		                  <?php } ?>
      		      </label>
			</div>
		</div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_prefix"><?php echo $entry_shipping_apiship_prefix; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_prefix" value="<?php echo $shipping_apiship_prefix; ?>" placeholder="" id="shipping_apiship_prefix" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_export_status"><?php echo $entry_shipping_apiship_export_status; ?></label>
            <div class="col-sm-8">
              <select name="shipping_apiship_export_status" id="shipping_apiship_export_status" class="form-control">
                  <?php foreach($order_statuses as $order_status) { ?>
                  <?php if ($order_status['order_status_id'] == $shipping_apiship_export_status) { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
	        	<?php } ?>
              	<?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_cancel_export_status"><?php echo $entry_shipping_apiship_cancel_export_status; ?></label>
            <div class="col-sm-8">
              <select name="shipping_apiship_cancel_export_status" id="shipping_apiship_cancel_export_status" class="form-control">
                  <?php foreach($order_statuses as $order_status) { ?>
                  <?php if ($order_status['order_status_id'] == $shipping_apiship_cancel_export_status) { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                  <?php } else { ?>
                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
	        	<?php } ?>
              	<?php } ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_paid_orders"><?php echo $entry_shipping_apiship_paid_orders; ?></label>
            <div class="col-sm-8">
			<div class="checkbox">
			<?php foreach($order_statuses as $order_status) { ?>
			<div class="form-control">
			<?php if (in_array($order_status['order_status_id'],$shipping_apiship_paid_orders)) { ?>
			<label><input type="checkbox" class="form-control" style="float:left" name="shipping_apiship_paid_orders[]" value="<?php echo $order_status['order_status_id']; ?>" checked="checked" /><?php echo $order_status['name']; ?></label>
			<?php } else { ?>
			<label><input type="checkbox" class="form-control" style="float:left" name="shipping_apiship_paid_orders[]" value="<?php echo $order_status['order_status_id']; ?>" /><?php echo $order_status['name']; ?></label>
			<?php } ?>
			</div>	
			<?php } ?>
			</div>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_sort_order"><?php echo $entry_shipping_apiship_sort_order; ?></label>
            <div class="col-sm-8">
              <input type="text" name="shipping_apiship_sort_order" value="<?php echo $shipping_apiship_sort_order; ?>" placeholder="" id="shipping_apiship_sort_order" class="form-control" />
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_mode"><?php echo $entry_shipping_apiship_mode; ?></label>
            <div class="col-sm-8">
		<select name="shipping_apiship_mode" class="form-control">
              	<?php foreach($shipping_apiship_modes as $mode) { ?>
              	<?php if ($mode['code'] == $shipping_apiship_mode) { ?>
              	<option value="<?php echo $mode['code']; ?>" selected="selected"><?php echo $mode['code_text']; ?></option>
              	<?php } else { ?>
              	<option value="<?php echo $mode['code']; ?>"><?php echo $mode['code_text']; ?></option>
              	<?php } ?>
              	<?php } ?>
            </select>
            </div>
          </div>

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_cron_url"><?php echo $entry_shipping_apiship_cron_url; ?></label>
            <div class="col-sm-8" style='display:flex;'>
		  	<input type="text" id="shipping_apiship_cron_url" value="<?php echo $shipping_apiship_cron_url; ?>" class="form-control" />
			<button id="shipping_apiship_cron_url_copy_button" title="<?php echo $text_shipping_apiship_cron_url_copy; ?>"><i class="fa fa-clipboard"></i></button>
            </div>
          </div>	

          <div class="form-group">
            <label class="col-sm-4 control-label" for="shipping_apiship_status"><?php echo $entry_shipping_apiship_status; ?></label>
            <div class="col-sm-8">
              <select name="shipping_apiship_status" id="shipping_apiship_status" class="form-control">
                  <?php if ($shipping_apiship_status) { ?>
                  <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                  <option value="0"><?php echo $text_disabled; ?></option>
                  <?php } else { ?>
                  <option value="1"><?php echo $text_enabled; ?></option>
                  <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                  <?php } ?>
              </select>
            </div>
          </div>						

      </form>


      </div>
    </div>
  </div>

		<div style="text-align:center; color:#555555;"><?php echo $heading_title; ?> v<?php echo $shipping_apiship_version; ?></div>

</div>


<link href="view/javascript/select2.min.css" type="text/css" rel="stylesheet" media="screen" />
<link href="view/javascript/select2-bootstrap.min.css" type="text/css" rel="stylesheet" media="screen" />
<script src="view/javascript/select2.full.min.js" type="text/javascript"></script>
<script src="view/javascript/i18n/ru.js" type="text/javascript"></script>
<style>
.select2-container { width: 100% !important; }
</style>

<script>

$('#shipping_apiship_cron_url_copy_button').click(function() {
	var copyText = document.getElementById("shipping_apiship_cron_url");
	copyText.select();
	document.execCommand("copy");
	$('#shipping_apiship_cron_url_copy_button').tooltip('enable')
	$('#shipping_apiship_cron_url_copy_button').tooltip('show');
	$('#shipping_apiship_cron_url_copy_button').tooltip('disable')
	return false;

});


$('.shipping_apiship_provider').select2({
  	ajax: {
    		url: '<?php echo $shipping_apiship_search_point_url; ?>',
	    data: function (params) {
	      var query = {
	        search: params.term,
	        type: $(this).attr('data-id')
	      }
	
	      return query;
	    },
    		dataType: 'json',
    		delay: 250,     

		processResults: function(data) {
	            var results = []; 
	            $.each(data, function (index, item) {
	                 results.push({
	                     id: item.id,
	                     text: item.code + ', ' + item.text
	                 });
	            });
	
	            return {
	              	results:results
	            };                            
	      },

  	},
    	minimumInputLength: 3,
	language: "ru"

});

function set_providers_checkbox(checked, provider) {
	if (checked)
		$("#shipping_apiship_provider_" + provider).prop("disabled", false);
	else
		$("#shipping_apiship_provider_" + provider).prop("disabled", true);

}

$(".shipping_apiship_provider_checkbox").on("click", function () {
	provider = $(this).attr('data-id')
	set_providers_checkbox(this.checked, provider)
});

jQuery('.shipping_apiship_provider_checkbox').each(function() {
	provider = $(this).attr('data-id')
	set_providers_checkbox(this.checked, provider)
});

function set_status_checkbox(checked, status) {
	if (checked)
		$("#shipping_apiship_mapping_use_" + status).prop("disabled", false);
	else
		$("#shipping_apiship_mapping_use_" + status).prop("disabled", true);

	if (checked)
		$("#shipping_apiship_mapping_notify_" + status).prop("disabled", false);
	else
		$("#shipping_apiship_mapping_notify_" + status).prop("disabled", true);

}


$(".shipping_apiship_mapping_checkbox_use").on("click", function () {
	status = $(this).attr('data-id')
	set_status_checkbox(this.checked, status)
});

jQuery('.shipping_apiship_mapping_checkbox_use').each(function() {
	status = $(this).attr('data-id')
	set_status_checkbox(this.checked, status)
});

</script>

<?php echo $footer; ?>