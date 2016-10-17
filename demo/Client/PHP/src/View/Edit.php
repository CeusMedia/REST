<?php
class View_Edit{

	public function __construct( $client, $request ){
		$this->client	= $client;
		$this->request	= $request;
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
				$rows[]	= '<div class="row-fluid"><div class="span4"><label>'.$key.'</label></div><div class="span8"><input type="text" name="key" value="'.htmlentities( $value, ENT_QUOTES, 'UTF-8' ).'"/></div></div>';
			}
		}

		$content	= '
<div class="content-panel">
	<h3>Edit: '.$id.'</h3>
	<div class="content-panel-inner">
		<form action="./?action=edit&id='.$id.'">
			'.join( $rows ).'
		</form>
	</div>
</div>';
		return $content;
	}
}
