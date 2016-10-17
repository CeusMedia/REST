<?php
class View_Index{

	public function __construct( $client, $request ){
		$this->client	= $client;
		$this->request	= $request;
	}

	public function render(){
		$data	= $this->client->get( 'test' );
		$rows	= array();
		foreach( $data['data']['items'] as $item ){
			$buttonEdit	= UI_HTML_Tag::create( 'a', 'edit', array(
				'class'		=> 'btn btn-small',
				'href'		=> '?action=edit&id='.$item['id'],
			) );
			$rows[]	= UI_HTML_Tag::create( 'tr', array(
				UI_HTML_Tag::create( 'td', $item['id'] ),
				UI_HTML_Tag::Create( 'td', date( 'Y-m-d', (float) $item['createdAt'] ) ),
				UI_HTML_Tag::create( 'td', $buttonEdit ),
			) );
		}
		$table	= UI_HTML_Tag::create( 'table', $rows, array( 'class' => 'table' ) );

		$content	= '
<div class="content-panel">
	<h3>Tests</h3>
	<div class="content-panel-inner">
		'.$table.'
	</div>
</div>';
		return $content;
	}
}
