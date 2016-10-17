<?php
class View_Add{

	public function __construct( $client, $request ){
		$this->client	= $client;
		$this->request	= $request;

		if( $this->request->has( 'save' ) ){
			$keys	= $this->request->get( 'key' );
			$values	= $this->request->get( 'values' );
			$data	= array();
			foreach( $keys as $nr => $key )
				if( strlen( trim( $key ) ) )
					$data[$key]	= $values[$nr];

			$data	= $this->client->post( 'test', $data );
			header( 'Location: ./?action=edit&id='.$data['data']['id'] );
		}
	}

	public function render(){
		$content	= '
<div class="content-panel">
	<h3>Add</h3>
	<div class="content-panel-inner">
		<form action="./?action=add" method="post">
			<div class="row-fluid">
				<div class="span4">
					<label>Key</label><br/>
					<input type="text" name="key[]" value=""/>
				</div>
				<div class="span8">
					<label>Value</label><br/>
					<input type="text" name="value[]" value=""/>
				</div>
			</div>

			<div class="row-fluid">
				<div class="span4">
					<label>Key</label><br/>
					<input type="text" name="key[]" value=""/>
				</div>
				<div class="span8">
					<label>Value</label><br/>
					<input type="text" name="value[]" value=""/>
				</div>
			</div>

			<div class="row-fluid">
				<div class="span4">
					<label>Key</label><br/>
					<input type="text" name="key[]" value=""/>
				</div>
				<div class="span8">
					<label>Value</label><br/>
					<input type="text" name="value[]" value=""/>
				</div>
			</div>

			<div class="buttonbar">
				<a href="./" class="btn btn-small">cancel</a>
				<button type="submit" name="save" class="btn btn-primary">save</button>
			</div>
		</form>
	</div>
</div>';
		return $content;
	}
}
