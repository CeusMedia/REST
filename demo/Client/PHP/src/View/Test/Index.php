<?php
class View_Test_Index extends View{

	public function render(){
		$items	= $this->get( 'items' );
		$rows	= array();
		foreach( $items as $item ){
			$buttonEdit	= UI_HTML_Tag::create( 'a', '<i class="fa fa-pencil"></i> edit', array(
				'class'		=> 'btn btn-small',
				'href'		=> 'Test/'.$item['id'].'/edit',
			) );
			$modifiedAt		= $item['modifiedAt'] ? $item['modifiedAt'] : $item['createdAt'];
			$rows[]	= UI_HTML_Tag::create( 'tr', array(
				UI_HTML_Tag::create( 'td', $item['id'] ),
				UI_HTML_Tag::Create( 'td', date( 'Y-m-d H:i:s', (float) $modifiedAt ) ),
				UI_HTML_Tag::create( 'td', $item['views'] ),
				UI_HTML_Tag::create( 'td', '<div class="btn-group">'.$buttonEdit.'</div>' ),
			) );
		}
		$colgroup	= UI_HTML_Elements::ColumnGroup( array( '100', '', '60', '100' ) );
		$thead	= UI_HTML_Tag::create( 'thead', UI_HTML_Elements::TableHeads( array( 'ID', 'Modification', 'Views', '' ) ) );
		$tbody	= UI_HTML_Tag::create( 'tbody', $rows );
		$table	= UI_HTML_Tag::create( 'table', $colgroup.$thead.$tbody, array( 'class' => 'table table-striped' ) );

		$content	= '
<div class="content-panel">
	<h3>Tests</h3>
	<div class="content-panel-inner">
		'.$table.'
		<div class="buttonbar">
			<a href="./Test/add" class="btn btn-primary">add</a>
		</div>
	</div>
</div>';
		return $content;
	}
}
