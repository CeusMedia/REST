<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

use CeusMedia\Common\UI\HTML\Elements as HtmlElements;
use CeusMedia\Common\UI\HTML\Tag as HtmlTag;
class View_Test_Index extends View
{
	public function render(): string
	{
		$items	= $this->get( 'items' );
		$rows	= array();
		foreach( $items as $item ){
			$buttonEdit	= HtmlTag::create( 'a', '<i class="fa fa-pencil"></i> edit', array(
				'class'		=> 'btn btn-small',
				'href'		=> 'Test/'.$item->id.'/edit',
			) );
			$modifiedAt		= $item->modifiedAt ? $item->modifiedAt : $item->createdAt;
			$rows[]	= HtmlTag::create( 'tr', array(
				HtmlTag::create( 'td', $item->id ),
				HtmlTag::Create( 'td', date( 'Y-m-d H:i:s', (float) $modifiedAt ) ),
				HtmlTag::create( 'td', $item->views ),
				HtmlTag::create( 'td', '<div class="btn-group">'.$buttonEdit.'</div>' ),
			) );
		}
		$colgroup	= HtmlElements::ColumnGroup( array( '100', '', '60', '100' ) );
		$thead	= HtmlTag::create( 'thead', HtmlElements::TableHeads( array( 'ID', 'Modification', 'Views', '' ) ) );
		$tbody	= HtmlTag::create( 'tbody', $rows );
		$table	= HtmlTag::create( 'table', $colgroup.$thead.$tbody, array( 'class' => 'table table-striped' ) );

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
