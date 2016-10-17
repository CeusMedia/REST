<?php
class View_Test_Remove extends View{

	public function render(){
		$id		= $this->get( 'id' );
		$data	= $this->get( 'item' );
		$rows	= array();
		foreach( $data['data'] as $key => $value ){
			if( in_array( $key, array( 'id' ) ) )
				continue;
			if( in_array( $key, array( 'createdAt', 'modifiedAt' ) ) && $value )
				$value	= date( 'Y-m-d H:i:s', $value );
			$rows[]	= '<dt>'.$key.'</dt><dd>'.htmlentities( $value, ENT_QUOTES, 'UTF-8' ).'</dd>';
		}
		$list	= '<dl class="dl-horizontal">'.join( $rows ).'</dl>';

		$content	= '
<div class="content-panel">
	<h3><span class="muted">Test:</span> Remove #'.$id.'</h3>
	<div class="content-panel-inner">
		<form action="./Test/'.$id.'/remove" method="post">
			'.$list.'
			<div class="buttonbar">
				<a href="./Test" class="btn"><i class="fa fa-list"></i> list</a>
				<a href="./Test/'.$id.'/edit" class="btn"><i class="fa fa-pencil"></i> edit</a>
				<button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> remove</button>
			</div>
		</form>
	</div>
</div>';
		return $content;
	}
}
