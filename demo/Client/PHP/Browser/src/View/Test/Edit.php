<?php
class View_Test_Edit extends View
{
	public function render(): string
	{
		$id		= $this->data->get( 'id' );
		$data	= $this->data->get( 'item' );
		$rows	= array();
		foreach( $data->data as $key => $value ){
			if( in_array( $key, ['id'] ) )
				continue;
			if( in_array( $key, ['createdAt', 'modifiedAt'] ) && $value )
				$value	= date( 'Y-m-d H:i:s', $value );
			if( in_array( $key, ['views', 'createdAt', 'modifiedAt'] ) )
				$value	= '<strong>'.$value.'</strong>';
			else
				$value	= '<input type="text" name="'.htmlentities( $key, ENT_QUOTES, 'UTF-8' ).'" value="'.htmlentities( $value, ENT_QUOTES, 'UTF-8' ).'"/>';
			$rows[]	= '<div class="row-fluid"><div class="span4"><label>'.$key.'</label></div><div class="span8">'.$value.'</div></div>';
		}

		$content	= '
<div class="content-panel">
	<h3><span class="muted">Test:</span> Edit #'.$id.'</h3>
	<div class="content-panel-inner">
		<form action="./Test/'.$id.'/edit" method="post">
			'.join( $rows ).'
			<div class="buttonbar">
				<a href="./Test" class="btn not-btn-small"><i class="fa fa-list"></i> list</a>
				<button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> save</button>
				<a href="./Test/'.$id.'/remove" class="btn not-btn-small btn-inverse"><i class="fa fa-trash"></i> remove</a>
			</div>
		</form>
	</div>
</div>';
		return $content;
	}
}
