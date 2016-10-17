<?php
class View_Edit{

	public function __construct( $client, $request ){
		$this->client	= $client;
		$this->request	= $request;

		if( $this->request->has( 'save' ) ){
			if( $this->request->get( 'id' ) ){
				$id		= $this->request->get( 'id' );
				$data	= $this->client->get( 'test/'.$id );
				foreach( $this->request->getAllFromSource( 'POST' ) as $key => $value )
					$data[$key]	= $value;
				$this->client->put( 'test/'.$id, $data );
				header( 'Location: ./?action=edit&id='.$id );
			}
		}
	}

	public function render(){
		$id		= $this->request->get( 'id' );
		$data	= $this->client->get( 'test/'.$id );
		$rows	= array();
		foreach( $data['data'] as $key => $value ){
			if( in_array( $key, array( 'id', 'createdAt', 'modifiedAt' ) ) ){
				$rows[]	= '<div class="row-fluid"><div class="span4"><label>'.$key.'</label></div><div class="span8"><pre>'.$value.'</pre></div></div>';
			}
			else{
				$rows[]	= '<div class="row-fluid"><div class="span4"><label>'.$key.'</label></div><div class="span8"><input type="text" name="'.htmlentities( $key, ENT_QUOTES, 'UTF-8' ).'" value="'.htmlentities( $value, ENT_QUOTES, 'UTF-8' ).'"/></div></div>';
			}
		}

		$content	= '
<div class="content-panel">
	<h3>Edit: '.$id.'</h3>
	<div class="content-panel-inner">
		<form action="./?action=edit&id='.$id.'" method="post">
			'.join( $rows ).'
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
